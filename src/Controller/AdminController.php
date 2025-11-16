<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(UserRepository $userRepo, TrajetRepository $trajetRepo): Response
    {
        $nbUsers = $userRepo->count([]);
        $nbTrajets = $trajetRepo->count([]);
        $nbTrajetsToday = $trajetRepo->countToday();

        return $this->render('admin/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbTrajets' => $nbTrajets,
            'nbTrajetsToday' => $nbTrajetsToday,
        ]);
    }
}
