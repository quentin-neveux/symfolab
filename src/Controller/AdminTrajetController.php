<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Form\TrajetType;
use App\Repository\TrajetRepository;
use App\Form\AdminTrajetEditType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/trajets', name: 'admin_trajets_')]
class AdminTrajetController extends AbstractController
{
    // ======================================================
    // 📋 LISTE DES TRAJETS
    // ======================================================
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        TrajetRepository $repo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $depart     = trim((string) $request->query->get('depart', ''));
        $arrivee    = trim((string) $request->query->get('arrivee', ''));
        $conducteur = trim((string) $request->query->get('conducteur', ''));
        $dateFilter = (string) $request->query->get('date', '');

        $qb = $repo->createQueryBuilder('t')
            ->leftJoin('t.conducteur', 'u')
            ->addSelect('u')
            ->orderBy('t.id', 'DESC');

        if ($depart !== '') {
            $qb->andWhere('t.villeDepart LIKE :depart')
               ->setParameter('depart', '%' . $depart . '%');
        }

        if ($arrivee !== '') {
            $qb->andWhere('t.villeArrivee LIKE :arrivee')
               ->setParameter('arrivee', '%' . $arrivee . '%');
        }

        if ($conducteur !== '') {
            $qb->andWhere('u.prenom LIKE :conducteur')
               ->setParameter('conducteur', '%' . $conducteur . '%');
        }

        // ======================================================
        // 📅 FILTRE TRAJETS DU JOUR
        // ======================================================
        if ($dateFilter === 'today') {
            $start = new \DateTimeImmutable('today');
            $end   = $start->modify('+1 day');

            $qb->andWhere('t.dateDepart >= :start')
               ->andWhere('t.dateDepart < :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        $trajets = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/trajets.html.twig', [
            'trajets' => $trajets,
        ]);
    }

    // ======================================================
    // 🔍 SHOW (détail admin)
    // ======================================================
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Trajet $trajet): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/trajet_show.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    // ======================================================
    // ✏️ EDIT (admin)
    // ======================================================
#[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
public function edit(
    Trajet $trajet,
    Request $request,
    EntityManagerInterface $em
): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $form = $this->createForm(AdminTrajetEditType::class, $trajet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Trajet mis à jour.');
        return $this->redirectToRoute('admin_trajets_index');
    }

    return $this->render('admin/trajet_edit.html.twig', [
        'trajet' => $trajet,
        'form' => $form->createView(),
    ]);
}

// ======================================================
// 🗑️ SUPPRIMER UN TRAJET (ADMIN)
// ======================================================
#[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
public function delete(
    Trajet $trajet,
    Request $request,
    EntityManagerInterface $em
): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    if (!$this->isCsrfTokenValid('delete_trajet_' . $trajet->getId(), $request->request->get('_token'))) {
        $this->addFlash('danger', 'Token CSRF invalide.');
        return $this->redirectToRoute('admin_trajets_index');
    }

    $em->remove($trajet);
    $em->flush();

    $this->addFlash('success', 'Trajet supprimé.');
    return $this->redirectToRoute('admin_trajets_index');
}


    // ======================================================
    // 🗑️ SUPPRIMER TOUS LES TRAJETS PASSÉS
    // ======================================================
    #[Route('/supprimer-passes', name: 'delete_past', methods: ['POST', 'GET'])]
    public function deletePast(
        TrajetRepository $repo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // optionnel : CSRF si tu le déclenches depuis un formulaire
        // $token = (string) $request->request->get('_token');
        // if ($token && !$this->isCsrfTokenValid('delete_past_trajets', $token)) { ... }

        $now = new \DateTimeImmutable();

        $trajetsPasses = $repo->createQueryBuilder('t')
            ->andWhere('t.dateDepart < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($trajetsPasses as $trajet) {
            $em->remove($trajet);
        }

        $em->flush();

        $this->addFlash('success', sprintf('%d trajet(s) passé(s) supprimé(s).', count($trajetsPasses)));
        return $this->redirectToRoute('admin_trajets_index');
    }

    // ======================================================
    // 💰 TRAJETS ÉLIGIBLES AU VERSEMENT
    // ======================================================
    #[Route('/payouts', name: 'payouts', methods: ['GET'])]
    public function payouts(TrajetRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // ⚠️ On protège : si tes champs n’existent pas, ça évite de casser en prod
        $qb = $repo->createQueryBuilder('t')
            ->orderBy('t.dateDepart', 'ASC');

        // Si ton entity a payoutStatus + conducteurConfirmeFin
        $qb->andWhere('t.conducteurConfirmeFin = true');

        // payoutStatus peut ne pas exister : on essaie seulement si la propriété existe côté Doctrine,
        // mais ici sans metadata, on garde simple : try/catch.
        try {
            $qb->andWhere('t.payoutStatus = :status')
               ->setParameter('status', 'PENDING');
        } catch (\Throwable) {
            // fallback : pas de filtre payoutStatus
        }

        $trajets = $qb->getQuery()->getResult();

        $eligibles = [];

        foreach ($trajets as $trajet) {
            // tous les passagers doivent avoir confirmé
            foreach ($trajet->getPassagers() as $reservation) {
                if (!method_exists($reservation, 'isPassagerConfirmeFin') || !$reservation->isPassagerConfirmeFin()) {
                    continue 2;
                }
            }
            $eligibles[] = $trajet;
        }

        return $this->render('admin/trajets_payouts.html.twig', [
            'trajets' => $eligibles,
        ]);
    }

    // ======================================================
    // ✅ VALIDATION ADMIN → VERSEMENT CONDUCTEUR
    // ======================================================
    #[Route('/payouts/{id}/valider', name: 'payouts_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validerPayout(
        Trajet $trajet,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // CSRF (recommandé)
        $token = (string) $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('payout_trajet_' . $trajet->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        if (method_exists($trajet, 'getPayoutStatus') && $trajet->getPayoutStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Ce trajet a déjà été traité.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        if (method_exists($trajet, 'isConducteurConfirmeFin') && !$trajet->isConducteurConfirmeFin()) {
            $this->addFlash('danger', 'Le conducteur n’a pas confirmé la fin.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        foreach ($trajet->getPassagers() as $reservation) {
            if (!method_exists($reservation, 'isPassagerConfirmeFin') || !$reservation->isPassagerConfirmeFin()) {
                $this->addFlash('danger', 'Tous les passagers n’ont pas confirmé la fin.');
                return $this->redirectToRoute('admin_trajets_payouts');
            }
        }

        $conducteur = $trajet->getConducteur();

        // payoutAmount / addEarnings = selon ton modèle
        $amount = 0;
        if (method_exists($trajet, 'getPayoutAmount')) {
            $amount = (int) $trajet->getPayoutAmount();
        } elseif (method_exists($trajet, 'getTokenCost')) {
            $amount = (int) $trajet->getTokenCost();
        }

        if ($conducteur) {
            if (method_exists($conducteur, 'addEarnings')) {
                $conducteur->addEarnings($amount);
            } elseif (method_exists($conducteur, 'setTokens') && method_exists($conducteur, 'getTokens')) {
                $conducteur->setTokens($conducteur->getTokens() + $amount);
            }
        }

        if (method_exists($trajet, 'setPayoutStatus')) {
            $trajet->setPayoutStatus('RELEASED');
        }

        $em->flush();

        $this->addFlash('success', sprintf(
            'Versement de %d ECR validé pour %s.',
            $amount,
            $conducteur?->getPrenom() ?? 'conducteur'
        ));

        return $this->redirectToRoute('admin_trajets_payouts');
    }

    // ======================================================
    // ⚠️ OUVRIR UN LITIGE
    // ======================================================
    #[Route('/{id}/litige', name: 'litige', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ouvrirLitige(
        Trajet $trajet,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $token = (string) $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('litige_trajet_' . $trajet->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        if (method_exists($trajet, 'getPayoutStatus') && $trajet->getPayoutStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Impossible de mettre ce trajet en litige.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        if (method_exists($trajet, 'setPayoutStatus')) {
            $trajet->setPayoutStatus('DISPUTED');
        }

        $em->flush();

        $this->addFlash('danger', 'Le trajet a été placé en litige.');
        return $this->redirectToRoute('admin_trajets_litiges');
    }

    // ======================================================
    // 📋 TRAJETS EN LITIGE
    // ======================================================
    #[Route('/litiges', name: 'litiges', methods: ['GET'])]
    public function litiges(TrajetRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // si payoutStatus existe
        try {
            $trajets = $repo->findBy(['payoutStatus' => 'DISPUTED'], ['dateDepart' => 'DESC']);
        } catch (\Throwable) {
            $trajets = [];
        }

        return $this->render('admin/trajets_litiges.html.twig', [
            'trajets' => $trajets,
        ]);
    }

    // ======================================================
    // ✅ RÉSOUDRE UN LITIGE (LIBÉRER PAIEMENT)
    // ======================================================
    #[Route('/litiges/{id}/valider', name: 'litige_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validerLitige(
        Trajet $trajet,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $token = (string) $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('litige_valider_' . $trajet->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_trajets_litiges');
        }

        if (method_exists($trajet, 'getPayoutStatus') && $trajet->getPayoutStatus() !== 'DISPUTED') {
            return $this->redirectToRoute('admin_trajets_litiges');
        }

        $conducteur = $trajet->getConducteur();

        $amount = 0;
        if (method_exists($trajet, 'getPayoutAmount')) {
            $amount = (int) $trajet->getPayoutAmount();
        } elseif (method_exists($trajet, 'getTokenCost')) {
            $amount = (int) $trajet->getTokenCost();
        }

        if ($conducteur) {
            if (method_exists($conducteur, 'addEarnings')) {
                $conducteur->addEarnings($amount);
            } elseif (method_exists($conducteur, 'setTokens') && method_exists($conducteur, 'getTokens')) {
                $conducteur->setTokens($conducteur->getTokens() + $amount);
            }
        }

        if (method_exists($trajet, 'setPayoutStatus')) {
            $trajet->setPayoutStatus('RELEASED');
        }

        $em->flush();

        $this->addFlash('success', 'Litige résolu. Paiement libéré.');
        return $this->redirectToRoute('admin_trajets_litiges');
    }
}
