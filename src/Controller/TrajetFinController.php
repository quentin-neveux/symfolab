<?php

namespace App\Controller;

use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetFinController extends AbstractController
{
    #[Route('/trajet/{id}/fin/conducteur', name: 'trajet_fin_conducteur')]
    public function conducteurValideTrajet(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {

        if ($trajet->getConducteur() !== $this->getUser()) {
            $this->addFlash('danger', 'Seul le conducteur peut valider la fin du trajet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        if ($trajet->isConducteurConfirmeFin()) {
            $this->addFlash('info', 'Tu as déjà validé la fin du trajet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        $trajet->setConducteurConfirmeFin(true);
        $em->flush();

        $this->addFlash('success', 'Fin du trajet confirmée (conducteur).');

        return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
    }

    // ----------------------------------------------------------
    // 🟢 2) Le passager confirme la fin du trajet

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

    // Vérifier que l'utilisateur est un passager
    $reservation = null;
    foreach ($trajet->getPassagers() as $tp) {
        if ($tp->getPassager() === $user) {
            $reservation = $tp;
            break;
        }
    }

    if (!$reservation) {
        $this->addFlash('danger', 'Tu n’es pas passager de ce trajet.');
        return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
    }

    if ($reservation->isPassagerConfirmeFin()) {
        $this->addFlash('info', 'Tu as déjà validé la fin du trajet.');
        return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
    }

    $reservation->setPassagerConfirmeFin(true);
    $em->flush();

    $this->addFlash('success', 'Fin du trajet confirmée (passager).');

    return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
}
}