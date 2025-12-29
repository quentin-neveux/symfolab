<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RechercheController extends AbstractController
{
    // ======================================================================
    // 🔍 PAGE DE RECHERCHE
    // ======================================================================
    #[Route('/recherche', name: 'app_recherche')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // --------------------------------------------------
        // PARAMÈTRES DE BASE
        // --------------------------------------------------
        $villeDepart = $request->query->get('ville_depart');
        $villeArrivee = $request->query->get('ville_arrivee');
        $dateDepart = $request->query->get('date_depart');

        // --------------------------------------------------
        // FILTRES
        // --------------------------------------------------
        $sort     = $request->query->get('sort', 'depart_asc');
        $energies = $request->query->all('energie');
        $prixMax  = $request->query->get('prix_max');

        // Sécurité : si aucune recherche → retour page covoiturer
        if (!$villeDepart && !$villeArrivee && !$dateDepart) {
            return $this->redirectToRoute('app_covoiturer');
        }

        // --------------------------------------------------
        // QUERY BUILDER PRINCIPAL
        // --------------------------------------------------
        $qb = $em->getRepository(Trajet::class)->createQueryBuilder('t');

        $qb
            ->leftJoin('t.vehicle', 'v')
            ->addSelect('v')
            ->andWhere('t.dateDepart > :now')
            ->andWhere('t.placesDisponibles > 0')
            ->setParameter('now', new \DateTime());

        // --------------------------------------------------
        // FILTRES DE RECHERCHE
        // --------------------------------------------------
        if ($villeDepart) {
            $qb
                ->andWhere('LOWER(t.villeDepart) LIKE LOWER(:vd)')
                ->setParameter('vd', '%' . $villeDepart . '%');
        }

        if ($villeArrivee) {
            $qb
                ->andWhere('LOWER(t.villeArrivee) LIKE LOWER(:va)')
                ->setParameter('va', '%' . $villeArrivee . '%');
        }

        if ($dateDepart) {
            $start = (new \DateTime($dateDepart))->setTime(0, 0, 0);
            $end   = (new \DateTime($dateDepart))->setTime(23, 59, 59);

            $qb
                ->andWhere('t.dateDepart BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        // --------------------------------------------------
        // FILTRE ÉNERGIE
        // --------------------------------------------------
        if (!empty($energies)) {
            $qb
                ->andWhere('v.energie IN (:energies)')
                ->setParameter('energies', $energies);
        }

        // --------------------------------------------------
        // FILTRE PRIX
        // --------------------------------------------------
        if ($prixMax !== null && $prixMax !== '') {
            $qb
                ->andWhere('t.price <= :prixMax')
                ->setParameter('prixMax', (float) $prixMax);
        }

        // --------------------------------------------------
        // TRI
        // --------------------------------------------------
        switch ($sort) {
            case 'prix_asc':
                $qb->orderBy('t.price', 'ASC');
                break;

            case 'depart_asc':
            default:
                $qb->orderBy('t.dateDepart', 'ASC');
                break;
        }

        // --------------------------------------------------
        // EXÉCUTION
        // --------------------------------------------------
        $trajets = $qb->getQuery()->getResult();

        // --------------------------------------------------
        // SUGGESTION SI AUCUN RÉSULTAT
        // --------------------------------------------------
        $trajetSuggestion = null;

        if (!$trajets) {
            $qb2 = $em->getRepository(Trajet::class)->createQueryBuilder('ts')
                ->andWhere('ts.dateDepart > :now')
                ->andWhere('ts.placesDisponibles > 0')
                ->setParameter('now', new \DateTime())
                ->orderBy('ts.dateDepart', 'ASC')
                ->setMaxResults(1);

            if ($villeDepart) {
                $qb2
                    ->andWhere('LOWER(ts.villeDepart) LIKE LOWER(:vd)')
                    ->setParameter('vd', '%' . $villeDepart . '%');
            }

            if ($villeArrivee) {
                $qb2
                    ->andWhere('LOWER(ts.villeArrivee) LIKE LOWER(:va)')
                    ->setParameter('va', '%' . $villeArrivee . '%');
            }

            $trajetSuggestion = $qb2->getQuery()->getOneOrNullResult();
        }

        // --------------------------------------------------
        // RENDER
        // --------------------------------------------------
        return $this->render('recherche/index.html.twig', [
            'trajets'          => $trajets,
            'ville_depart'     => $villeDepart,
            'ville_arrivee'    => $villeArrivee,
            'date_depart'      => $dateDepart,
            'trajetSuggestion' => $trajetSuggestion,
        ]);
    }

    // ======================================================================
    // 🔵 DETAIL TRAJET
    // ======================================================================
    #[Route('/trajet/{id}', name: 'app_trajet_detail', requirements: ['id' => '\d+'])]
    public function detail(
        Trajet $trajet,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        if (!$this->getUser()) {
            $request->getSession()->set(
                'redirect_after_login',
                $this->generateUrl('app_trajet_detail', ['id' => $trajet->getId()])
            );

            return $this->redirectToRoute('app_connexion');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // --------------------------------------------------
        // AVIS CONDUCTEUR
        // --------------------------------------------------
        $reviewRepo = $em->getRepository(Review::class);
        $averageRating = $reviewRepo->getAverageRatingForUser($trajet->getConducteur()->getId());
        $reviews = $reviewRepo->getReviewsForUser($trajet->getConducteur()->getId());

        // --------------------------------------------------
        // RÉSERVATION
        // --------------------------------------------------
        $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ]);

        $canConfirmEnd = false;
        $canReview = false;

        if ($reservation) {
            if (!$reservation->isPassagerConfirmeFin() && $trajet->isFinished()) {
                $canConfirmEnd = true;
            }

            if ($trajet->isFinished() && $user !== $trajet->getConducteur()) {
                $existingReview = $reviewRepo->findOneBy([
                    'author' => $user,
                    'target' => $trajet->getConducteur(),
                    'trajet' => $trajet,
                ]);

                if (!$existingReview) {
                    $canReview = true;
                }
            }
        }

        return $this->render('trajet/detail.html.twig', [
            'trajet'        => $trajet,
            'averageRating' => $averageRating,
            'reviews'       => $reviews,
            'reservation'   => $reservation,
            'canConfirmEnd' => $canConfirmEnd,
            'canReview'     => $canReview,
        ]);
    }
}
