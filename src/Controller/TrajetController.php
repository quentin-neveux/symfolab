<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Service\DistanceCalculator;
use App\Service\TokenCalculator;
use App\Form\TrajetType;
use App\Form\TrajetEditType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrajetController extends AbstractController
{
    // ----------------------------------------------------------
    // 🟢 Proposer un nouveau trajet
    // ----------------------------------------------------------
    #[Route('/profil/proposer-trajet', name: 'app_proposer_trajet')]
    public function proposer(
        Request $request,
        EntityManagerInterface $em,
        DistanceCalculator $distanceCalc,
        TokenCalculator $tokenCalc
    ): Response {
        if (!$this->getUser()) {
            $request->getSession()->set('redirect_after_login', $request->getUri());
            return $this->redirectToRoute('app_connexion');
        }

        $trajet = new Trajet();
        $form = $this->createForm(TrajetType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ➤ Distance estimée entre les deux villes
            $distance = $distanceCalc->estimateDistance(
                $trajet->getVilleDepart(),
                $trajet->getVilleArrivee()
            );

            // ➤ Coût en tokens par service
            $tokenCost = $tokenCalc->calculate($distance);
            $trajet->setTokenCost($tokenCost);

            // ➤ Conducteur : l'utilisateur actuel
            $trajet->setConducteur($this->getUser());

            $em->persist($trajet);
            $em->flush();

            $this->addFlash('success', "Votre trajet a bien été publié. Coût : $tokenCost tokens");
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/proposer.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // ----------------------------------------------------------
    // 🟡 Mes trajets (Avenir / En cours / Passés)
    // ----------------------------------------------------------
    #[Route('/profil/mes_trajets', name: 'app_mes_trajets')]
    public function mesTrajets(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour voir tes trajets.');
            return $this->redirectToRoute('app_connexion');
        }

        $repo = $em->getRepository(Trajet::class);
        $tpRepo = $em->getRepository(TrajetPassager::class);
        $now = new \DateTime();

        $trajetsConducteur = $repo->findBy(['conducteur' => $user], ['dateDepart' => 'ASC']);
        $trajetsPassager   = $tpRepo->findBy(['passager' => $user]);

        $trajetsAvenir = [];
        $trajetsEnCours = [];
        $trajetsPasses = [];

        // --- Conducteur ---
        foreach ($trajetsConducteur as $trajet) {
            $item = [
                'trajet' => $trajet,
                'role' => 'conducteur',
                'reservation' => null
            ];

            if ($trajet->getDateDepart() > $now) {
                $trajetsAvenir[] = $item;
            } elseif ($trajet->getDateArrivee() && $trajet->getDateArrivee() < $now) {
                $trajetsPasses[] = $item;
            } else {
                $trajetsEnCours[] = $item;
            }
        }

        // --- Passager ---
        foreach ($trajetsPassager as $res) {
            $trajet = $res->getTrajet();

            $item = [
                'trajet' => $trajet,
                'role' => 'passager',
                'reservation' => $res
            ];

            if ($trajet->getDateDepart() > $now) {
                $trajetsAvenir[] = $item;
            } elseif ($trajet->getDateArrivee() && $trajet->getDateArrivee() < $now) {
                $trajetsPasses[] = $item;
            } else {
                $trajetsEnCours[] = $item;
            }
        }

        return $this->render('trajet/trajet_historique.html.twig', [
            'trajetsAvenir' => $trajetsAvenir,
            'trajetsEnCours' => $trajetsEnCours,
            'trajetsPasses' => $trajetsPasses,
        ]);
    }


    // ----------------------------------------------------------
    // 🔎 Détail d’un trajet
    // ----------------------------------------------------------
    #[Route('/trajet/{id}', name: 'app_trajet_detail')]
    public function detail(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ReviewRepository $reviewRepo
    ): Response {
        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        if (!$this->getUser()) {
            $request->getSession()->set('redirect_after_login', $request->getUri());
        }

        $conducteur = $trajet->getConducteur();

        $averageRating = $conducteur 
            ? $reviewRepo->getAverageRatingForUser($conducteur->getId()) 
            : null;

        $reviews = $conducteur 
            ? $reviewRepo->getReviewsForUser($conducteur->getId()) 
            : [];

        $reservation = null;
        if ($this->getUser()) {
            $reservation = $em->getRepository(TrajetPassager::class)->findOneBy([
                'trajet' => $trajet,
                'passager' => $this->getUser()
            ]);
        }

        return $this->render('trajet/detail.html.twig', [
            'trajet' => $trajet,
            'averageRating' => $averageRating,
            'reviews' => $reviews,
            'reservation' => $reservation,
        ]);
    }


    // ----------------------------------------------------------
    // ✏️ Modifier un trajet
    // ----------------------------------------------------------
    #[Route('/profil/trajet/{id}/edit', name: 'app_trajet_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        if ($trajet->getConducteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres trajets.');
        }

        $form = $this->createForm(TrajetEditType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Mise à jour de l'heure si modifiée
            $newTime = $form->get('dateDepart')->getData();

            if ($newTime) {
                $date = $trajet->getDateDepart();
                $date->setTime(
                    (int)substr($newTime, 0, 2),
                    (int)substr($newTime, 3, 2)
                );
                $trajet->setDateDepart($date);
            }

            $em->flush();

            $this->addFlash('success', 'Trajet modifié avec succès.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/edit.html.twig', [
            'trajet' => $trajet,
            'form' => $form->createView(),
        ]);
    }


    // ----------------------------------------------------------
    // ❌ Supprimer un trajet
    // ----------------------------------------------------------
    #[Route('/profil/trajet/{id}/delete', name: 'app_trajet_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        if ($trajet->getConducteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Tu ne peux supprimer que tes propres trajets.');
        }

        $reservations = $em->getRepository(TrajetPassager::class)
            ->findBy(['trajet' => $trajet]);

        foreach ($reservations as $res) {

            $user = $res->getPassager();

            if ($res->isPaid()) {
                $user->setTokens($user->getTokens() + $trajet->getTokenCost());
            }

            $em->remove($res);
        }

        $em->remove($trajet);
        $em->flush();

        $this->addFlash('success', 'Trajet supprimé. Les passagers ont été remboursés.');

        return $this->redirectToRoute('app_mes_trajets');
    }


    // ----------------------------------------------------------
    // ❌ Annuler une réservation (passager)
    // ----------------------------------------------------------
    #[Route('/trajet/{id}/annuler', name: 'trajet_annuler_reservation')]
    public function annulerReservation(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour annuler une réservation.');
            return $this->redirectToRoute('app_connexion');
        }

        $trajet = $em->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        $reservation = $em->getRepository(TrajetPassager::class)
            ->findOneBy([
                'trajet' => $trajet,
                'passager' => $user
            ]);

        if (!$reservation) {
            $this->addFlash('danger', 'Tu ne participes pas à ce trajet.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        if ($reservation->isPaid()) {
            $montant = $trajet->getTokenCost();
            $user->setTokens($user->getTokens() + $montant);
        }

        $trajet->setPlacesDisponibles($trajet->getPlacesDisponibles() + 1);

        $em->remove($reservation);
        $em->flush();

        $this->addFlash('success', '🚗 Réservation annulée. Remboursement effectué.');

        return $this->redirectToRoute('app_mes_trajets');
    }
}
