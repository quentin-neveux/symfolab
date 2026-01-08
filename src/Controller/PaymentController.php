<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
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

#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly MailerService $mailer
    ) {}

    // =========================================================
    // 🟢 PAGE DE PAIEMENT
    // =========================================================
    #[Route('/trajet/{id}/payment', name: 'trajet_payment')]
    public function paiement(
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // 🚫 Déjà réservé → succès direct
        if ($tpRepo->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ])) {
            return $this->redirectToRoute('trajet_payment_success', [
                'id' => $trajet->getId()
            ]);
        }

        return $this->render('payment/payment.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    // =========================================================
    // 🔵 VALIDATION DU PAIEMENT → RÉSERVATION
    // =========================================================
    #[Route('/trajet/{id}/payment/validate', name: 'trajet_payment_validate', methods: ['POST'])]
    public function validerPaiement(
        Request $request,
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // 🔐 CSRF
        if (!$this->isCsrfTokenValid(
            'payment_trajet_' . $trajet->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        // 🚫 Anti double paiement
        if ($tpRepo->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ])) {
            return $this->redirectToRoute('trajet_payment_success', [
                'id' => $trajet->getId()
            ]);
        }

        // 🚫 Plus de place
        if ($trajet->getPlacesDisponibles() <= 0) {
            $this->addFlash('danger', 'Ce trajet est complet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        // --------------------------------------------------
        // 💳 Coût total tokens = trajet + plateforme
        // --------------------------------------------------
        $trajetTokens = $trajet->getTokenCost();               // coût du trajet (tokens)
        $platformFee  = Trajet::PLATFORM_FEE_TOKENS;           // 2
        $totalCost    = $trajetTokens + $platformFee;

        if ($totalCost <= 0) {
            // Sécurité : ne devrait jamais arriver si tokenCost >= 0
            $this->addFlash('danger', 'Coût du trajet invalide.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        // 🚫 Tokens insuffisants (TOTAL)
        if ($user->getTokens() < $totalCost) {
            $this->addFlash(
                'danger',
                sprintf('Solde de tokens insuffisant. Coût total : %d tokens (trajet %d + frais plateforme %d).',
                    $totalCost,
                    $trajetTokens,
                    $platformFee
                )
            );
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        // --------------------------------------------------
        // TRANSACTION ATOMIQUE
        // --------------------------------------------------
        $em->beginTransaction();

        try {
            // ➕ Réservation
            $reservation = new TrajetPassager();
            $reservation->setTrajet($trajet);
            $reservation->setPassager($user);

            // snapshot des coûts (important pour annulation/remboursement)
            $reservation->setTokenCostCharged($trajetTokens);
            $reservation->setPlatformFeeCharged($platformFee);

            // payé
            $reservation->setIsPaid(true);

            $em->persist($reservation);

            // ➖ Débit tokens TOTAL
            $user->removeTokens($totalCost);

            // Trace transaction (TOTAL)
            $debit = new TokenTransaction();
            $debit->setUser($user);
            $debit->setAmount($totalCost);
            $debit->setType('DEBIT');
            $debit->setReason('RESERVATION_TRAJET');
            $debit->setTrajetId($trajet->getId());
            $em->persist($debit);

            // ➖ Place disponible
            $trajet->setPlacesDisponibles(
                $trajet->getPlacesDisponibles() - 1
            );

            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }

        // 📧 MAILS APRÈS PAIEMENT RÉUSSI
        $this->mailer->notifyReservationConfirmed($trajet, $user); // PASSAGER
        $this->mailer->notifyNewPassenger($trajet, $user);         // CONDUCTEUR

        return $this->redirectToRoute('trajet_payment_success', [
            'id' => $trajet->getId()
        ]);
    }

    // =========================================================
    // ✅ PAGE PAIEMENT RÉUSSI
    // =========================================================
    #[Route('/trajet/{id}/payment/success', name: 'trajet_payment_success')]
    public function succes(Trajet $trajet): Response
    {
        return $this->render('payment/payment_success.html.twig', [
            'trajet' => $trajet,
        ]);
    }
}
