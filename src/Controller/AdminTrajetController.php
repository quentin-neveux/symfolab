<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Form\AdminTrajetType;
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
    #[Route('/', name: 'index')]
public function index(TrajetRepository $repo, Request $request, PaginatorInterface $paginator): Response
{
    $depart      = $request->query->get('depart');
    $arrivee     = $request->query->get('arrivee');
    $conducteur  = $request->query->get('conducteur');

    $qb = $repo->createQueryBuilder('t')
        ->leftJoin('t.conducteur', 'u')
        ->addSelect('u')
        ->orderBy('t.id', 'DESC');

    if ($depart) {
        $qb->andWhere('t.villeDepart LIKE :depart')
           ->setParameter('depart', '%'.$depart.'%');
    }

    if ($arrivee) {
        $qb->andWhere('t.villeArrivee LIKE :arrivee')
           ->setParameter('arrivee', '%'.$arrivee.'%');
    }

    if ($conducteur) {
        $qb->andWhere('u.prenom LIKE :conducteur')
           ->setParameter('conducteur', '%'.$conducteur.'%');
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


    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Trajet $trajet, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AdminTrajetType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_trajets_list');
        }

        return $this->render('admin/trajet_edit.html.twig', [
            'form' => $form,
            'trajet' => $trajet
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Trajet $trajet, EntityManagerInterface $em): Response
    {
    // 🔍 Récupérer toutes les réservations associées au trajet
    $reservations = $em->getRepository(\App\Entity\TrajetPassager::class)
        ->findBy(['trajet' => $trajet]);

    foreach ($reservations as $res) {

        $user = $res->getPassager();

        // 🌱 Remboursement si payé
        if ($res->isPaid()) {
            $user->setTokens($user->getTokens() + $trajet->getTokenCost());
        }

        // ❌ Suppression de la réservation
        $em->remove($res);
    }

    // ❌ Suppression du trajet lui-même
    $em->remove($trajet);

    // ✔️ Sauvegarde en BDD
    $em->flush();

    $this->addFlash('success', 'Trajet supprimé. Tous les passagers payés ont été remboursés.');

    return $this->redirectToRoute('admin_trajets_index');
    }



    #[Route('/delete-past', name: 'delete_past')]
    public function deletePast(TrajetRepository $repo, EntityManagerInterface $em): Response
    {
    $now = new \DateTimeImmutable();

    // Tous les trajets passés
    $pastTrajets = $repo->createQueryBuilder('t')
        ->where('t.dateArrivee < :now')
        ->setParameter('now', $now)
        ->getQuery()
        ->getResult();

    foreach ($pastTrajets as $trajet) {

        // 🔍 Récupération des réservations du trajet
        $reservations = $em->getRepository(\App\Entity\TrajetPassager::class)
            ->findBy(['trajet' => $trajet]);

        foreach ($reservations as $res) {

            $user = $res->getPassager();

            // 🌱 Remboursement si payé
            if ($res->isPaid()) {
                $user->setTokens(
                    $user->getTokens() + $trajet->getTokenCost()
                );
            }

            // ❌ Suppression de la réservation
            $em->remove($res);
        }

        // ❌ Suppression du trajet
        $em->remove($trajet);
    }

    $em->flush();

    $this->addFlash('success', 'Les trajets passés ont été supprimés et les passagers payés remboursés.');
    return $this->redirectToRoute('admin_trajets_index');
}


}
