<?php

namespace App\Controller;

use App\Entity\User;
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
    // 🟢 Dashboard Admin
    // ----------------------------------------------------------
    #[Route('/', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        TrajetRepository $trajetRepo
    ): Response {
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $nbUsers = $userRepo->count([]);
        $nbTrajets = $trajetRepo->count([]);
        $nbTrajetsToday = $trajetRepo->countToday();

        return $this->render('admin/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbTrajets' => $nbTrajets,
            'nbTrajetsToday' => $nbTrajetsToday,
        ]);
    }

    // ----------------------------------------------------------
    // 🟣 Commande rapide : /admin/addtokens/{userId}/{amount}
    // ----------------------------------------------------------
    #[Route('/addtokens/{userId}/{amount}', name: 'addtokens')]
    public function addTokens(
        int $userId,
        int $amount,
        EntityManagerInterface $em
    ): Response {
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur introuvable.");
        }

        if ($amount <= 0) {
            $this->addFlash('danger', "Le montant doit être supérieur à 0.");
            return $this->redirectToRoute('admin_dashboard');
        }

        // Ajouter les tokens
        $user->setTokens($user->getTokens() + $amount);
        $em->flush();

        $this->addFlash(
            'success', 
            "✔️ {$amount} tokens ajoutés à {$user->getEmail()}"
        );

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/trajets/{id}', name: 'admin_trajet_show', requirements: ['id' => '\d+'])]
public function showTrajet(Trajet $trajet): Response
{
    return $this->render('admin/trajets/show.html.twig', [
        'trajet' => $trajet
    ]);
}
    
}
