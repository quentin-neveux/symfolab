<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepo, TrajetRepository $trajetRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig', [
            'userCount' => count($userRepo->findAll()),
            'trajetCount' => count($trajetRepo->findAll()),
            'admin' => $this->getUser(),
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/users.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/trajets', name: 'app_admin_trajets')]
    public function trajets(TrajetRepository $trajetRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/trajets.html.twig', [
            'trajets' => $trajetRepo->findAll(),
        ]);
    }
}
