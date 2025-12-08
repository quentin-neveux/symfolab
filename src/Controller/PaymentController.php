<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Repository\TrajetPassagerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    #[Route('/trajet/{id}/paiement', name: 'payment_page')]
    public function payer(
        Trajet $trajet,
        TrajetPassagerRepository $tpRepo,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour payer ta réservation.');
            return $this->redirectToRoute('app_connexion');
        }

        // Vérifier que ce user a bien réservé ce trajet
        $reservation = $tpRepo->findOneBy([
            'trajet' => $trajet,
            'passager' => $user
        ]);

        if (!$reservation) {
            $this->addFlash('danger', 'Tu dois réserver ce trajet avant de pouvoir payer.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // Déjà payé ?
        if ($reservation->isPaid()) {
            $this->addFlash('info', 'Tu as déjà payé ce trajet.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        // Vérifier tokens disponibles
        $prix = $trajet->getTokenCost();

        if ($user->getTokens() < $prix) {
            $this->addFlash('danger', 'Solde insuffisant. Recharge tes tokens.');
            return $this->redirectToRoute('app_profil');
        }

        // Déduire tokens
        $user->setTokens($user->getTokens() - $prix);

        // Valider le paiement sur la réservation
        $reservation->setIsPaid(true);

        $em->flush();

        $this->addFlash('success', 'Paiement effectué 🎉');

        return $this->redirectToRoute('app_trajet_detail', [
            'id' => $trajet->getId()
        ]);
    }
}
