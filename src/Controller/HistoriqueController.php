<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HistoriqueController extends AbstractController
{
    #[Route('/historique/trajet/{id}', name: 'app_historique_trajet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Trajet $trajet,
        EntityManagerInterface $em,
        ReviewRepository $reviewRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user,
        ]);

        $isConducteur = ($trajet->getConducteur()?->getId() === $user->getId());

        // 🔒 Cette page historique ne doit être visible que si tu as un lien avec le trajet
        if (!$isConducteur && !$reservation) {
            throw $this->createAccessDeniedException();
        }

        $passagers = $em->getRepository(TrajetPassager::class)->findBy([
            'trajet' => $trajet
        ]);

        $averageRating = $reviewRepo->getAverageRatingForUser(
            $trajet->getConducteur()->getId()
        );

        return $this->render('historique/trajet_show.html.twig', [
            'trajet'        => $trajet,
            'reservation'   => $reservation,
            'passagers'     => $passagers,
            'averageRating' => $averageRating,
            'isConducteur'  => $isConducteur,
        ]);
    }
}
