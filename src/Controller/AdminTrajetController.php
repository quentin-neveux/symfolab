<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/trajets', name: 'admin_trajets_')]
class AdminTrajetController extends AbstractController
{
    // ======================================================
    // 📋 LISTE DES TRAJETS
    // ======================================================
    #[Route('/', name: 'index')]
    public function index(
        TrajetRepository $repo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $depart     = $request->query->get('depart');
        $arrivee    = $request->query->get('arrivee');
        $conducteur = $request->query->get('conducteur');
        $dateFilter = $request->query->get('date'); // 👈 NOUVEAU
    
        $qb = $repo->createQueryBuilder('t')
            ->leftJoin('t.conducteur', 'u')
            ->addSelect('u')
            ->orderBy('t.id', 'DESC');
    
        if ($depart) {
            $qb->andWhere('t.villeDepart LIKE :depart')
               ->setParameter('depart', '%' . $depart . '%');
        }
    
        if ($arrivee) {
            $qb->andWhere('t.villeArrivee LIKE :arrivee')
               ->setParameter('arrivee', '%' . $arrivee . '%');
        }
    
        if ($conducteur) {
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
    // 🗑️ SUPPRIMER TOUS LES TRAJETS PASSÉS
    // ======================================================
    #[Route('/supprimer-passes', name: 'delete_past')]
    public function deletePast(
        TrajetRepository $repo,
        EntityManagerInterface $em
    ): Response {
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

        $this->addFlash(
            'success',
            sprintf('%d trajet(s) passé(s) supprimé(s).', count($trajetsPasses))
        );

        return $this->redirectToRoute('admin_trajets_index');
    }

    // ======================================================
    // 💰 TRAJETS ÉLIGIBLES AU VERSEMENT
    // ======================================================
    #[Route('/payouts', name: 'payouts')]
    public function payouts(TrajetRepository $repo): Response
    {
        $trajets = $repo->createQueryBuilder('t')
            ->andWhere('t.payoutStatus = :status')
            ->andWhere('t.conducteurConfirmeFin = true')
            ->setParameter('status', 'PENDING')
            ->orderBy('t.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();

        $eligibles = [];

        foreach ($trajets as $trajet) {
            foreach ($trajet->getPassagers() as $reservation) {
                if (!$reservation->isPassagerConfirmeFin()) {
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
    #[Route('/payouts/{id}/valider', name: 'payouts_valider', methods: ['POST'])]
    public function validerPayout(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {
        if ($trajet->getPayoutStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Ce trajet a déjà été traité.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        if (!$trajet->isConducteurConfirmeFin()) {
            $this->addFlash('danger', 'Le conducteur n’a pas confirmé la fin.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        foreach ($trajet->getPassagers() as $reservation) {
            if (!$reservation->isPassagerConfirmeFin()) {
                $this->addFlash(
                    'danger',
                    'Tous les passagers n’ont pas confirmé la fin.'
                );
                return $this->redirectToRoute('admin_trajets_payouts');
            }
        }

        $conducteur = $trajet->getConducteur();
        $conducteur->addEarnings($trajet->getPayoutAmount());

        $trajet->setPayoutStatus('RELEASED');

        $em->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Versement de %s EcoCrédits ECR effectué au conducteur %s.',
                $trajet->getPayoutAmount(),
                $conducteur->getPrenom()
            )
        );

        return $this->redirectToRoute('admin_trajets_payouts');
    }

    // ======================================================
    // ⚠️ OUVRIR UN LITIGE
    // ======================================================
    #[Route('/{id}/litige', name: 'litige', methods: ['POST'])]
    public function ouvrirLitige(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {
        if ($trajet->getPayoutStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Impossible de mettre ce trajet en litige.');
            return $this->redirectToRoute('admin_trajets_payouts');
        }

        $trajet->setPayoutStatus('DISPUTED');
        $em->flush();

        $this->addFlash('danger', 'Le trajet a été placé en litige.');
        return $this->redirectToRoute('admin_trajets_litiges');
    }

    // ======================================================
    // 📋 TRAJETS EN LITIGE
    // ======================================================
    #[Route('/litiges', name: 'litiges')]
    public function litiges(TrajetRepository $repo): Response
    {
        $trajets = $repo->findBy(
            ['payoutStatus' => 'DISPUTED'],
            ['dateDepart' => 'DESC']
        );

        return $this->render('admin/trajets_litiges.html.twig', [
            'trajets' => $trajets,
        ]);
    }

    // ======================================================
    // ✅ RÉSOUDRE UN LITIGE (LIBÉRER PAIEMENT)
    // ======================================================
    #[Route('/litiges/{id}/valider', name: 'litige_valider', methods: ['POST'])]
    public function validerLitige(
        Trajet $trajet,
        EntityManagerInterface $em
    ): Response {
        if ($trajet->getPayoutStatus() !== 'DISPUTED') {
            return $this->redirectToRoute('admin_trajets_litiges');
        }

        $conducteur = $trajet->getConducteur();
        $conducteur->addEarnings($trajet->getPayoutAmount());

        $trajet->setPayoutStatus('RELEASED');

        $em->flush();

        $this->addFlash('success', 'Litige résolu. Paiement libéré.');
        return $this->redirectToRoute('admin_trajets_litiges');
    }
}
