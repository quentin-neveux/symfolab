<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TokenTransaction;
use App\Entity\User;
use App\Repository\TrajetPassagerRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TrajetPassagerController extends AbstractController
{
    public function __construct(
        private readonly MailerService $mailerService
    ) {}

    // ----------------------------------------------------------
    // 🟡 CONFIRMATION DE RÉSERVATION (PAGE)
    // ----------------------------------------------------------
    #[IsGranted('ROLE_USER')]
    #[Route('/trajet/{id}/reserver/confirm', name: 'trajet_reserver_confirm')]
    public function confirmReservation(Trajet $trajet): Response
    {
        return $this->render('trajet/confirm_reservation.html.twig', [
            'trajet' => $trajet
        ]);
    }

    // ----------------------------------------------------------
    // 🟢 RÉSERVER UN TRAJET → REDIRECT PAIEMENT (SANS BDD)
    // ----------------------------------------------------------
    #[IsGranted('ROLE_USER')]
    #[Route('/trajet/{id}/reserver', name: 'trajet_reserver', methods: ['POST'])]
    public function reserver(
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // 🔒 déjà réservé (peu importe payé ou non : on évite doublon)
        if ($tpRepo->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ])) {
            $this->addFlash('info', 'Tu as déjà réservé ce trajet.');
            return $this->redirectToRoute('trajet_payment_success', [
                'id' => $trajet->getId()
            ]);
        }

        // 🔒 complet
        if ($trajet->getPlacesDisponibles() <= 0) {
            $this->addFlash('danger', 'Ce trajet est complet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        // 🔒 Solde tokens : coût trajet + fee plateforme
        $totalCost = $trajet->getTotalTokenCost(); // tokenCost + 2
        if ($user->getTokens() < $totalCost) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Solde de tokens insuffisant. Coût total : %d tokens (trajet %d + plateforme %d).',
                    $totalCost,
                    $trajet->getTokenCost(),
                    Trajet::PLATFORM_FEE_TOKENS
                )
            );
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        // ➜ paiement géré dans PaymentController (il doit débiter TOTAL et créer TrajetPassager)
        return $this->redirectToRoute('trajet_payment', [
            'id' => $trajet->getId()
        ]);
    }

    // ----------------------------------------------------------
    // ❌ ANNULER UNE RÉSERVATION (PASSAGER) + CSRF + POST ONLY + REDIRECT DYNAMIQUE
    // ----------------------------------------------------------
    #[IsGranted('ROLE_USER')]
    #[Route('/trajet/{id}/annuler', name: 'trajet_annuler', methods: ['POST'])]
    public function annuler(
        Request $request,
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // ✅ CSRF
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('annuler_trajet_' . $trajet->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $reservation = $tpRepo->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ]);

        if (!$reservation) {
            $this->addFlash('danger', 'Aucune réservation trouvée.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        if ($trajet->getDateDepart() <= new \DateTimeImmutable()) {
            $this->addFlash('danger', 'Trajet déjà commencé.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        $em->beginTransaction();

        try {
            // 💳 remboursement UNIQUEMENT si payé
            if ($reservation->isPaid()) {
                $refundAmount = $reservation->getTotalTokensCharged();

                $user->addTokens($refundAmount);

                $refund = new TokenTransaction();
                $refund->setUser($user);
                $refund->setAmount($refundAmount);
                $refund->setType('CREDIT');
                $refund->setReason('REFUND_ANNULATION');
                $refund->setTrajetId($trajet->getId());
                $em->persist($refund);
            }

            // ➕ place libérée (capée à la capacité passagers du véhicule)
            $maxPassagers = max(0, $trajet->getVehicle()->getPlaces() - 1);
            $newPlaces = min($maxPassagers, $trajet->getPlacesDisponibles() + 1);
            $trajet->setPlacesDisponibles($newPlaces);

            $em->remove($reservation);
            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }

        // ✉️ mails
        $this->mailerService->notifyCancellationByPassenger($trajet, $user);

        $this->addFlash(
            'info',
            $reservation->isPaid()
                ? sprintf('Réservation annulée. %d tokens remboursés.', $reservation->getTotalTokensCharged())
                : 'Réservation annulée.'
        );

        // ✅ Redirect dynamique (fourni par le form) sinon home
        $redirect = (string) $request->request->get('redirect', '');
        if ($redirect) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('app_home');
    }
}
