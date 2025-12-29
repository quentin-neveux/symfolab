<?php

namespace App\Controller;

use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfilGainsController extends AbstractController
{
    #[Route('/profil/gains', name: 'app_profil_gains')]
    public function gains(TrajetRepository $trajetRepo): Response
    {
        $user = $this->getUser();

        // Trajets conduits avec versement
        $trajets = $trajetRepo->createQueryBuilder('t')
            ->andWhere('t.conducteur = :user')
            ->andWhere('t.payoutStatus IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('t.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('profil/gains.html.twig', [
            'trajets' => $trajets,
            'earnings' => $user->getEarnings(),
        ]);
    }
}
