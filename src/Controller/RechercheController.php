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
        // PARAMÈTRES DE BASE + trim()
        // --------------------------------------------------
        $villeDepart  = $request->query->get('ville_depart');
        $villeArrivee = $request->query->get('ville_arrivee');
        $dateDepart   = $request->query->get('date_depart');

        $villeDepart  = $villeDepart  ? trim((string) $villeDepart)  : null;
        $villeArrivee = $villeArrivee ? trim((string) $villeArrivee) : null;
        $dateDepart   = $dateDepart   ? trim((string) $dateDepart)   : null;

        // --------------------------------------------------
        // FILTRES
        // --------------------------------------------------
        $sort = (string) $request->query->get('sort', 'depart_asc');

        // ✅ IMPORTANT : Symfony 6+ => all() n'accepte PAS d'argument
        $energies = $request->query->all()['energie'] ?? [];
        if (!is_array($energies)) {
            $energies = [$energies];
        }

        $tokenMaxRaw = $request->query->get('prix_max');

        // Sécurité : si aucune recherche → retour page covoiturer
        if (!$villeDepart && !$villeArrivee && !$dateDepart) {
            return $this->redirectToRoute('app_home');
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
            ->setParameter('now', new \DateTimeImmutable());

        // --------------------------------------------------
        // FILTRES DE RECHERCHE (villes)
        // --------------------------------------------------
        if ($villeDepart) {
            $qb
                ->andWhere('LOWER(t.villeDepart) LIKE LOWER(:vd)')
                ->setParameter('vd', $villeDepart . '%');
        }

        if ($villeArrivee) {
            $qb
                ->andWhere('LOWER(t.villeArrivee) LIKE LOWER(:va)')
                ->setParameter('va', $villeArrivee . '%');
        }

        // --------------------------------------------------
        // FILTRE DATE (uniquement si date saisie)
        // --------------------------------------------------
        if ($dateDepart) {
            $start = (new \DateTimeImmutable($dateDepart))->setTime(0, 0, 0);
            $end   = (new \DateTimeImmutable($dateDepart))->setTime(23, 59, 59);

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
        // FILTRE TOKENS (prix_max)
        // --------------------------------------------------
        $tokenMax = null;
        if ($tokenMaxRaw !== null && $tokenMaxRaw !== '') {
            $tokenMax = (int) $tokenMaxRaw;
            if ($tokenMax > 0) {
                $qb
                    ->andWhere('t.tokenCost <= :tokenMax')
                    ->setParameter('tokenMax', $tokenMax);
            }
        }

        // --------------------------------------------------
        // TRI
        // --------------------------------------------------
        switch ($sort) {
            case 'prix_asc':
                $qb->orderBy('t.tokenCost', 'ASC');
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
        // ✅ FALLBACK : mêmes villes, autres dates (prochains trajets)
        // UNIQUEMENT si une date est saisie ET aucun résultat
        // --------------------------------------------------
        $trajetsFallback = [];
        $fallbackActive  = false;

        if (empty($trajets) && $dateDepart && ($villeDepart || $villeArrivee)) {
            $fallbackActive = true;

            $qbFallback = $em->getRepository(Trajet::class)->createQueryBuilder('tf')
                ->leftJoin('tf.vehicle', 'vf')
                ->addSelect('vf')
                ->andWhere('tf.dateDepart > :now')
                ->andWhere('tf.placesDisponibles > 0')
                ->setParameter('now', new \DateTimeImmutable());

            if ($villeDepart) {
                $qbFallback
                    ->andWhere('LOWER(tf.villeDepart) LIKE LOWER(:vd_fb)')
                    ->setParameter('vd_fb', $villeDepart . '%');
            }

            if ($villeArrivee) {
                $qbFallback
                    ->andWhere('LOWER(tf.villeArrivee) LIKE LOWER(:va_fb)')
                    ->setParameter('va_fb', $villeArrivee . '%');
            }

            if (!empty($energies)) {
                $qbFallback
                    ->andWhere('vf.energie IN (:energies_fb)')
                    ->setParameter('energies_fb', $energies);
            }

            if ($tokenMax !== null && $tokenMax > 0) {
                $qbFallback
                    ->andWhere('tf.tokenCost <= :tokenMax_fb')
                    ->setParameter('tokenMax_fb', $tokenMax);
            }

            // ✅ pas de filtre date ici
            $qbFallback
                ->orderBy('tf.dateDepart', 'ASC')
                ->setMaxResults(10);

            $trajetsFallback = $qbFallback->getQuery()->getResult();
        }

        // --------------------------------------------------
        // Suggestion (1 trajet) : uniquement quand date saisie + aucun résultat
        // (on prend le 1er du fallback si dispo)
        // --------------------------------------------------
        $trajetSuggestion = null;
        if (empty($trajets) && $dateDepart) {
            $trajetSuggestion = $trajetsFallback[0] ?? null;
        }

        return $this->render('recherche/index.html.twig', [
            'trajets'          => $trajets,
            'trajetsFallback'  => $trajetsFallback,
            'fallbackActive'   => $fallbackActive,

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
    public function detail(Trajet $trajet, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            $request->getSession()->set(
                'redirect_after_login',
                $this->generateUrl('app_trajet_detail', ['id' => $trajet->getId()])
            );
            return $this->redirectToRoute('app_connexion');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $reviewRepo     = $em->getRepository(Review::class);
        $averageRating  = $reviewRepo->getAverageRatingForUser($trajet->getConducteur()->getId());
        $reviews        = $reviewRepo->getReviewsForUser($trajet->getConducteur()->getId());

        $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
            'trajet'   => $trajet,
            'passager' => $user
        ]);

        $passagers = $em->getRepository(TrajetPassager::class)->findBy([
            'trajet' => $trajet
        ]);

        $canConfirmEnd = false;
        $canReview     = false;

        if ($reservation) {
            if (!$reservation->isPassagerConfirmeFin() && $trajet->isConducteurConfirmeFin()) {
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
            'passagers'     => $passagers,
            'canConfirmEnd' => $canConfirmEnd,
            'canReview'     => $canReview,
        ]);
    }
}
