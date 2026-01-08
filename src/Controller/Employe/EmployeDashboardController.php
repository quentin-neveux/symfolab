<?php

namespace App\Controller\Employe;

use App\Repository\DisputeRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
#[Route('/employe')]
class EmployeDashboardController extends AbstractController
{
    #[Route('', name: 'employe_dashboard')]
    public function index(DisputeRepository $disputes, ReviewRepository $reviews): Response
    {
        return $this->render('employe/dashboard.html.twig', [
            'openDisputes'  => $disputes->findOpenFirst(),
            'latestReviews' => $reviews->findBy([], ['createdAt' => 'DESC'], 15),
        ]);
    }
}
