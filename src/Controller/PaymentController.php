<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\TokenTransaction;
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
        private MailerService $mailer
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

        // 🔐 CSRF
        if (!$this->isCsrfTokenValid(
            'payment_trajet_' . $trajet->getId(),
            $request->request->get('_token')
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

        // 🚫 Tokens insuffisants
        if ($user->getTokens() < 2) {
            $this->addFlash('danger', 'Solde de tokens insuffisant pour les frais plateforme.');
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
            $reservation->setIsPaid(true);
            $em->persist($reservation);

            // ➖ Débit tokens
            $user->setTokens($user->getTokens() - 2);

            $debit = new TokenTransaction();
            $debit->setUser($user);
            $debit->setAmount(2);
            $debit->setType('DEBIT');
            $debit->setReason('FRAIS_PLATEFORME');
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
        $this->mailer->notifyReservationConfirmed($trajet, $user); // ✅ PASSAGER
        $this->mailer->notifyNewPassenger($trajet, $user);         // ✅ CONDUCTEUR

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
