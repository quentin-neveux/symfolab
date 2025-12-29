<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TokenTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/tokens', name: 'admin_tokens_')]
class AdminTokensController extends AbstractController
{
    // =========================================================
    // 📊 DASHBOARD TOKENS (USERS + SOLDE)
    // =========================================================
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('search', ''));

        $qb = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        if ($search !== '') {
            $qb->andWhere(
                'u.email LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s OR CAST(u.id AS string) LIKE :s'
            )
            ->setParameter('s', '%' . $search . '%');
        }

        $users = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25 // 
        );

        $txRepo = $em->getRepository(TokenTransaction::class);

        $totalCredit = (int) $txRepo->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.amount), 0)')
            ->where('t.type = :type')
            ->setParameter('type', 'CREDIT')
            ->getQuery()
            ->getSingleScalarResult();

        $totalDebit = (int) $txRepo->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.amount), 0)')
            ->where('t.type = :type')
            ->setParameter('type', 'DEBIT')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/tokens/index.html.twig', [
            'users'           => $users,
            'search'          => $search,
            'totalCredit'     => $totalCredit,
            'totalDebit'      => $totalDebit,
            'platformBalance' => $totalCredit - $totalDebit,
        ]);
    }

    // =========================================================
    // 🔄 MODIFICATION DE TOKENS PAR ADMIN
    // =========================================================
    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userId = (int) $request->request->get('user');
        $amount = (int) $request->request->get('amount');

        if ($userId <= 0 || $amount === 0) {
            $this->addFlash('warning', 'Utilisateur ou montant invalide.');
            return $this->redirectToRoute('admin_tokens_index');
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_tokens_index');
        }

        if ($user->getTokens() + $amount < 0) {
            $this->addFlash('danger', 'Solde insuffisant.');
            return $this->redirectToRoute('admin_tokens_index');
        }

        $user->setTokens($user->getTokens() + $amount);

        $transaction = new TokenTransaction();
        $transaction->setUser($user);
        $transaction->setAmount(abs($amount));
        $transaction->setType($amount > 0 ? 'CREDIT' : 'DEBIT');
        $transaction->setReason($amount > 0 ? 'ADMIN_ADD' : 'ADMIN_REMOVE');

        $em->persist($transaction);
        $em->flush();

        $this->addFlash(
            'success',
            sprintf('Solde mis à jour pour %s (%+d tokens).', $user->getEmail(), $amount)
        );

        return $this->redirectToRoute('admin_tokens_index');
    }

    // =========================================================
    // 📜 HISTORIQUE DES TRANSACTIONS (PAGINÉ)
    // =========================================================
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $qb = $em->getRepository(TokenTransaction::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->addSelect('u')
            ->orderBy('t.createdAt', 'DESC');

        $transactions = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            30
        );

        return $this->render('admin/tokens/history.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    // =========================================================
    // ♻️ RESET COMPTABILITÉ TOKENS
    // =========================================================
    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->createQuery('DELETE FROM App\Entity\TokenTransaction t')
           ->execute();

        $reset = new TokenTransaction();
        $reset->setAmount(0);
        $reset->setType('RESET');
        $reset->setReason('ADMIN_RESET');

        $em->persist($reset);
        $em->flush();

        $this->addFlash('success', 'La comptabilité des tokens a été réinitialisée.');

        return $this->redirectToRoute('admin_tokens_index');
    }
}
