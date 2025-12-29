<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trajet;
use App\Repository\UserRepository;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    // ----------------------------------------------------------
    // 🟢 DASHBOARD ADMIN
    // ----------------------------------------------------------
    #[Route('/', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        TrajetRepository $trajetRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $usersCount   = $userRepo->count([]);
        $trajetsCount = $trajetRepo->count([]);
        $trajetsToday = $trajetRepo->countToday(); // ✔ méthode repo

        return $this->render('admin/dashboard.html.twig', [
            'usersCount'   => $usersCount,
            'trajetsCount' => $trajetsCount,
            'trajetsToday' => $trajetsToday, // ✔ NOM ALIGNÉ AVEC TWIG
        ]);
    }

    // ----------------------------------------------------------
    // 🟣 COMMANDE RAPIDE : AJOUT DE TOKENS (ADMIN)
    // ----------------------------------------------------------
    #[Route('/addtokens/{userId}/{amount}', name: 'addtokens', requirements: [
        'userId' => '\d+',
        'amount' => '\d+'
    ])]
    public function addTokens(
        int $userId,
        int $amount,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($amount <= 0) {
            $this->addFlash('danger', 'Le montant doit être supérieur à 0.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user->setTokens($user->getTokens() + $amount);
        $em->flush();

        $this->addFlash(
            'success',
            sprintf('✔️ %d tokens ajoutés à %s', $amount, $user->getEmail())
        );

        return $this->redirectToRoute('admin_dashboard');
    }

    // ----------------------------------------------------------
    // 🔍 AFFICHAGE D’UN TRAJET (ADMIN)
    // ----------------------------------------------------------
    #[Route('/trajets/{id}', name: 'trajet_show', requirements: ['id' => '\d+'])]
    public function showTrajet(Trajet $trajet): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/trajets/show.html.twig', [
            'trajet' => $trajet,
        ]);
    }
}
