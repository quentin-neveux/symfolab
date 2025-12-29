<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetFinController extends AbstractController
{
    // ----------------------------------------------------------
    // 🟢 1) Le conducteur confirme la fin du trajet
    // ----------------------------------------------------------
    #[Route('/trajet/{id}/fin/conducteur', name: 'trajet_fin_conducteur')]
    public function conducteurValideTrajet(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {

        if ($trajet->getConducteur() !== $this->getUser()) {
            $this->addFlash('danger', 'Seul le conducteur peut valider la fin du trajet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        if ($trajet->isConducteurConfirmeFin()) {
            $this->addFlash('info', 'Tu as déjà validé la fin du trajet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        $trajet->setConducteurConfirmeFin(true);

        // 🔥 Tentative de déclenchement automatique du paiement
        foreach ($trajet->getPassagers() as $reservation) {
            $this->tryToProcessPayment($reservation);
        }

        $em->flush();

        $this->addFlash('success', 'Fin du trajet confirmée (conducteur).');

        return $this->redirectToRoute('app_trajet_detail', [
            'id' => $trajet->getId()
        ]);
    }

    // ----------------------------------------------------------
    // 🟢 2) Le passager confirme la fin du trajet
    // ----------------------------------------------------------
    #[Route('/trajet/{id}/fin/passager', name: 'trajet_fin_passager')]
    public function passagerValideTrajet(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Connecte-toi pour valider la fin du trajet.');
            return $this->redirectToRoute('app_connexion');
        }

        $reservation = null;
        foreach ($trajet->getPassagers() as $tp) {
            if ($tp->getPassager() === $user) {
                $reservation = $tp;
                break;
            }
        }

        if (!$reservation) {
            $this->addFlash('danger', 'Tu n’es pas passager de ce trajet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        if ($reservation->isPassagerConfirmeFin()) {
            $this->addFlash('info', 'Tu as déjà validé la fin du trajet.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        $reservation->setPassagerConfirmeFin(true);

        // 🔥 Tentative de déclenchement automatique du paiement
        $this->tryToProcessPayment($reservation);

        $em->flush();

        $this->addFlash('success', 'Fin du trajet confirmée (passager).');

        return $this->redirectToRoute('app_trajet_detail', [
            'id' => $trajet->getId()
        ]);
    }

// ----------------------------------------------------------
// 🔥 LOGIQUE MÉTIER : déclenchement automatique du paiement
// ----------------------------------------------------------
private function tryToProcessPayment(TrajetPassager $reservation): void
{
    $trajet = $reservation->getTrajet();

    if (
        $reservation->isAuthorized()
        && $trajet->isConducteurConfirmeFin()
        && $reservation->isPassagerConfirmeFin()
        && !$reservation->isPaid()
    ) {
        // 💳 Paiement encaissé par la plateforme (simulation)
        $reservation->setIsPaid(true);
        $reservation->setPaidAt(new \DateTimeImmutable());
    }
}
}
