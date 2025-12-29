<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetPassagerRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TrajetPassagerController extends AbstractController
{
    public function __construct(
        private MailerService $mailerService
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

        // 🔒 tokens
        if ($user->getTokens() < 2) {
            $this->addFlash('warning', 'Solde de tokens insuffisant.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
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

        // ➜ paiement géré dans PaymentController
        return $this->redirectToRoute('trajet_payment', [
            'id' => $trajet->getId()
        ]);
    }

    // ----------------------------------------------------------
    // ❌ ANNULER UNE RÉSERVATION (PASSAGER)
    // ----------------------------------------------------------
    #[IsGranted('ROLE_USER')]
    #[Route('/trajet/{id}/annuler', name: 'trajet_annuler')]
    public function annuler(
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

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

        if ($trajet->getDateDepart() <= new \DateTime()) {
            $this->addFlash('danger', 'Trajet déjà commencé.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        $em->beginTransaction();

        try {
            // 💳 remboursement
            $user->setTokens($user->getTokens() + 2);

            $refund = new \App\Entity\TokenTransaction();
            $refund->setUser($user);
            $refund->setAmount(2);
            $refund->setType('CREDIT');
            $refund->setReason('REFUND_ANNULATION');
            $refund->setTrajetId($trajet->getId());
            $em->persist($refund);

            // ➕ place libérée
            $trajet->setPlacesDisponibles(
                $trajet->getPlacesDisponibles() + 1
            );

            $em->remove($reservation);
            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }

        // ✉️ mails
        $this->mailerService->notifyCancellationByPassenger($trajet, $user);

        $this->addFlash('info', 'Réservation annulée.');

        return $this->redirectToRoute('app_trajet_detail', [
            'id' => $trajet->getId()
        ]);
    }
}
