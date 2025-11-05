<?php

namespace App\Controller;

use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RechercheController extends AbstractController
{
    #[Route('/recherche', name: 'app_recherche')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $villeDepart = $request->query->get('ville_depart');
        $villeArrivee = $request->query->get('ville_arrivee');
        $dateDepart = $request->query->get('date_depart');

        if (!$villeDepart && !$villeArrivee && !$dateDepart) {
            return $this->redirectToRoute('app_covoiturer');
        }

        $qb = $em->getRepository(Trajet::class)->createQueryBuilder('t');

        if ($villeDepart) {
            $qb->andWhere('LOWER(t.villeDepart) LIKE LOWER(:villeDepart)')
               ->setParameter('villeDepart', "%$villeDepart%");
        }

        if ($villeArrivee) {
            $qb->andWhere('LOWER(t.villeArrivee) LIKE LOWER(:villeArrivee)')
               ->setParameter('villeArrivee', "%$villeArrivee%");
        }

        if ($dateDepart) {
            $qb->andWhere('DATE(t.dateDepart) = :dateDepart')
               ->setParameter('dateDepart', new \DateTime($dateDepart));
        }

        $qb->orderBy('t.dateDepart', 'ASC');
        $trajets = $qb->getQuery()->getResult();

        return $this->render('recherche/index.html.twig', [
            'trajets' => $trajets,
            'ville_depart' => $villeDepart,
            'ville_arrivee' => $villeArrivee,
            'date_depart' => $dateDepart,
        ]);
    }

    #[Route('/trajet/{id}', name: 'app_trajet_detail', requirements: ['id' => '\d+'])]
    public function detail(Trajet $trajet, Request $request): Response
    {
        if (!$this->getUser()) {
            $request->getSession()->set('redirect_after_login', $this->generateUrl('app_trajet_detail', [
                'id' => $trajet->getId(),
            ]));
            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('trajet/detail.html.twig', [
            'trajet' => $trajet,
        ]);
    }
}
