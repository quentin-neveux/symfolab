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

        $villeDepart  = $villeDepart  ? trim($villeDepart)  : null;
        $villeArrivee = $villeArrivee ? trim($villeArrivee) : null;

        // --------------------------------------------------
        // FILTRES
        // --------------------------------------------------
        $sort        = $request->query->get('sort', 'depart_asc');
        $energies    = $request->query->all('energie');
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
        // FILTRES DE RECHERCHE AVEC TRIM()
        // --------------------------------------------------
        if ($villeDepart) {
            $qb
                ->andWhere('LOWER(TRIM(t.villeDepart)) LIKE LOWER(:vd)')
                ->setParameter('vd', $villeDepart . '%');
        }

        if ($villeArrivee) {
            $qb
                ->andWhere('LOWER(TRIM(t.villeArrivee)) LIKE LOWER(:va)')
                ->setParameter('va', $villeArrivee . '%');
        }

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
        // FILTRE TOKENS (ancien "prix_max")
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
        // - uniquement si une date a été saisie ET aucun résultat
        // - on garde les mêmes filtres (energie, tokenMax)
        // --------------------------------------------------
        $trajetsFallback = [];
        $fallbackActive = false;

        if (!$trajets && $dateDepart && ($villeDepart || $villeArrivee)) {
            $fallbackActive = true;

            $qbFallback = $em->getRepository(Trajet::class)->createQueryBuilder('tf')
                ->leftJoin('tf.vehicle', 'vf')
                ->addSelect('vf')
                ->andWhere('tf.dateDepart > :now')
                ->andWhere('tf.placesDisponibles > 0')
                ->setParameter('now', new \DateTimeImmutable());

            if ($villeDepart) {
                $qbFallback
                    ->andWhere('LOWER(TRIM(tf.villeDepart)) LIKE LOWER(:vd_fb)')
                    ->setParameter('vd_fb', $villeDepart . '%');
            }

            if ($villeArrivee) {
                $qbFallback
                    ->andWhere('LOWER(TRIM(tf.villeArrivee)) LIKE LOWER(:va_fb)')
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

            // ✅ IMPORTANT : ici on NE remet PAS le filtre de date exact
            $qbFallback
                ->orderBy('tf.dateDepart', 'ASC')
                ->setMaxResults(10);

            $trajetsFallback = $qbFallback->getQuery()->getResult();
        }

        // --------------------------------------------------
        // SUGGESTION (1 seul trajet) - on laisse, mais on l'aligne :
        // si fallback existe, suggestion devient le 1er du fallback
        // --------------------------------------------------
        $trajetSuggestion = null;

        if (!$trajets) {
            if (!empty($trajetsFallback)) {
                $trajetSuggestion = $trajetsFallback[0];
            } else {
                $qb2 = $em->getRepository(Trajet::class)->createQueryBuilder('ts')
                    ->leftJoin('ts.vehicle', 'v2')
                    ->addSelect('v2')
                    ->andWhere('ts.dateDepart > :now')
                    ->andWhere('ts.placesDisponibles > 0')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->orderBy('ts.dateDepart', 'ASC')
                    ->setMaxResults(1);

                if ($villeDepart) {
                    $qb2
                        ->andWhere('LOWER(TRIM(ts.villeDepart)) LIKE LOWER(:vd_sug)')
                        ->setParameter('vd_sug', $villeDepart . '%');
                }

                if ($villeArrivee) {
                    $qb2
                        ->andWhere('LOWER(TRIM(ts.villeArrivee)) LIKE LOWER(:va_sug)')
                        ->setParameter('va_sug', $villeArrivee . '%');
                }

                // ⚠️ ici on ne filtre PAS sur la date exacte non plus,
                // sinon ta suggestion redevient inutile quand la date ne matche pas.
                if (!empty($energies)) {
                    $qb2
                        ->andWhere('v2.energie IN (:energies_sug)')
                        ->setParameter('energies_sug', $energies);
                }

                if ($tokenMax !== null && $tokenMax > 0) {
                    $qb2
                        ->andWhere('ts.tokenCost <= :tokenMax_sug')
                        ->setParameter('tokenMax_sug', $tokenMax);
                }

                $trajetSuggestion = $qb2->getQuery()->getOneOrNullResult();
            }
        }

        // --------------------------------------------------
        // RENDER
        // --------------------------------------------------
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

        // --------------------------------------------------
        // PASSAGERS (pour la liste côté Twig)
        // --------------------------------------------------
        $passagers = $em->getRepository(TrajetPassager::class)->findBy([
            'trajet' => $trajet
        ]);

        $canConfirmEnd = false;
        $canReview = false;

        if ($reservation) {

            // ✅ le passager peut confirmer la fin si le conducteur a confirmé (et lui pas encore)
            if (!$reservation->isPassagerConfirmeFin() && $trajet->isConducteurConfirmeFin()) {
                $canConfirmEnd = true;
            }

            // ✅ avis uniquement quand le trajet est VRAIMENT terminé (finished=true)
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
