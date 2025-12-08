<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Repository\TrajetPassagerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetPassagerController extends AbstractController
{
    // ----------------------------------------------------------
    // 🟢 1) Réserver un trajet
    // ----------------------------------------------------------
    #[Route('/trajet/{id}/reserver', name: 'trajet_reserver')]
    public function reserver(
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour réserver un trajet.');
            return $this->redirectToRoute('app_connexion');
        }

        // 🚫 Déjà réservé ?
        $existing = $tpRepo->findOneBy([
            'trajet' => $trajet,
            'passager' => $user
        ]);

        if ($existing) {
            $this->addFlash('info', 'Tu as déjà réservé ce trajet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 🚫 Plus de places ?
        if ($trajet->getPlacesDisponibles() <= 0) {
            $this->addFlash('danger', 'Ce trajet est complet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // 🟢 Création de la réservation
        $tp = new TrajetPassager();
        $tp->setTrajet($trajet);
        $tp->setPassager($user);

        $em->persist($tp);

        // On retire une place disponible
        $trajet->setPlacesDisponibles($trajet->getPlacesDisponibles() - 1);

        $em->flush();

        $this->addFlash('success', 'Réservation effectuée ! Tu peux payer maintenant ou plus tard.');

        return $this->redirectToRoute('app_trajet_detail', [
        'id' => $trajet->getId()
        ]);

    }

    // ----------------------------------------------------------
    // 🚫 2) Annuler une réservation
    // ----------------------------------------------------------

    #[Route('/trajet/{id}/annuler', name: 'trajet_annuler')]
public function annuler(
    Trajet $trajet,
    TrajetPassagerRepository $tpRepo,
    EntityManagerInterface $em
): Response {

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('warning', 'Connecte-toi pour annuler ta réservation.');
        return $this->redirectToRoute('app_connexion');
    }

    // 🔍 Trouver la réservation
    $reservation = $tpRepo->findOneBy([
        'trajet' => $trajet,
        'passager' => $user
    ]);

    if (!$reservation) {
        $this->addFlash('danger', 'Aucune réservation trouvée.');
        return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
    }

    // 🟡 Si pas payé → pas de remboursement
    if ($reservation->isPaid()) {
        $user->setTokens($user->getTokens() + $trajet->getTokenCost());
    }

    // 🟢 Rendre la place
    $trajet->setPlacesDisponibles($trajet->getPlacesDisponibles() + 1);

    // 🟢 Supprimer la réservation
    $em->remove($reservation);

    // 🟢 Sauvegarde
    $em->flush();

    if ($reservation->isPaid()) {
        $this->addFlash('success', 'Réservation annulée ✔️ — Tokens remboursés.');
    } else {
        $this->addFlash('info', 'Réservation annulée.');
    }

    return $this->redirectToRoute('app_trajet_detail', [
        'id' => $trajet->getId()
    ]);
}

}
