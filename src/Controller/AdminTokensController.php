<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/tokens', name: 'admin_tokens_')]
class AdminTokensController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('search', ''));
        $repo = $em->getRepository(User::class);

        if ($search !== '') {
            $users = $repo->createQueryBuilder('u')
                ->where('u.email LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s')
                ->orWhere('u.id LIKE :s2')
                ->orWhere('u.tokens LIKE :s2')
                ->setParameter('s', '%' . $search . '%')
                ->setParameter('s2', '%' . (string)$search . '%')
                ->orderBy('u.id', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $users = $repo->findBy([], ['id' => 'ASC']);
        }

        return $this->render('admin/tokens/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // Récupération du montant envoyé par le formulaire
        $amount = (int) $request->request->get('amount');

        // Montant invalide
        if ($amount <= 0) {
            $this->addFlash('warning', 'Le montant doit être supérieur à 0.');
            return $this->redirectToRoute('admin_tokens_index');
        }

        // 🔥 AJOUT DES TOKENS — C’ÉTAIT LA PARTIE MANQUANTE
        $user->setTokens($user->getTokens() + $amount);

        // 💾 Sauvegarde
        $em->flush(); // pas de persist() -> l'entity existe déjà

        $this->addFlash(
            'success',
            sprintf('%d tokens ajoutés à %s.', $amount, $user->getEmail())
        );

        return $this->redirectToRoute('admin_tokens_index');
    }
}
