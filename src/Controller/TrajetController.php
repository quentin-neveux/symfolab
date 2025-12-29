<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\Vehicle;
use App\Form\TrajetType;
use App\Form\TrajetEditType;
use App\Repository\ReviewRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TrajetController extends AbstractController
{
    use TargetPathTrait;

    // ==========================================================
    // 🟢 PROPOSER UN TRAJET
    // ==========================================================
    #[Route('/profil/proposer-trajet', name: 'app_proposer_trajet')]
    public function proposer(
        Request $request,
        EntityManagerInterface $em,
        MailerService $mailer
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
            return $this->redirectToRoute('app_connexion');
        }

        $trajet = new Trajet();
        $trajet->setConducteur($user);

        $form = $this->createForm(TrajetType::class, $trajet, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Vehicle|null $newVehicle */
            $newVehicle = $form->get('newVehicle')->getData();

            if ($newVehicle) {
                $newVehicle->setOwner($user);
                $em->persist($newVehicle);
                $trajet->setVehicle($newVehicle);
            }

            if (!$trajet->getVehicle()) {
                $this->addFlash('danger', 'Tu dois sélectionner ou ajouter un véhicule.');
                return $this->redirectToRoute('app_proposer_trajet');
            }

            $em->persist($trajet);
            $em->flush();

            // ✉️ Mail conducteur
            $mailer->notifyTrajetCreated($trajet);

            $this->addFlash('success', 'Ton trajet a bien été publié.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        return $this->render('trajet/proposer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ==========================================================
    // 🔍 DÉTAIL TRAJET
    // ==========================================================
    #[Route('/trajet/{id}', name: 'app_trajet_detail')]
    public function detail(
        Trajet $trajet,
        EntityManagerInterface $em,
        ReviewRepository $reviewRepo
    ): Response {
        $user = $this->getUser();
        $reservation = null;

        if ($user) {
            $reservation = $em->getRepository(TrajetPassager::class)
                ->findOneBy(['trajet' => $trajet, 'passager' => $user]);
        }

        $passagers = $em->getRepository(TrajetPassager::class)
            ->findBy(['trajet' => $trajet]);

        $averageRating = $reviewRepo->getAverageRatingForUser(
            $trajet->getConducteur()->getId()
        );

        return $this->render('trajet/detail.html.twig', [
            'trajet'        => $trajet,
            'reservation'   => $reservation,
            'passagers'     => $passagers,
            'averageRating' => $averageRating,
        ]);
    }

    // ==========================================================
    // 🟡 MES TRAJETS
    // ==========================================================
    #[Route('/profil/mes_trajets', name: 'app_mes_trajets')]
    public function mesTrajets(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connecte-toi pour voir tes trajets.');
            return $this->redirectToRoute('app_connexion');
        }

        $trajetRepo = $em->getRepository(Trajet::class);
        $tpRepo     = $em->getRepository(TrajetPassager::class);
        $now        = new \DateTimeImmutable();

        $trajetsConducteur = $trajetRepo->findBy(
            ['conducteur' => $user],
            ['dateDepart' => 'ASC']
        );

        $trajetsPassager = $tpRepo->findBy(['passager' => $user]);

        $avenir = $encours = $passes = [];

        foreach ($trajetsConducteur as $trajet) {
            $this->classifyTrajet($trajet, 'conducteur', null, $now, $avenir, $encours, $passes);
        }

        foreach ($trajetsPassager as $reservation) {
            $trajet = $reservation->getTrajet();
            $this->classifyTrajet($trajet, 'passager', $reservation, $now, $avenir, $encours, $passes);
        }

        return $this->render('trajet/trajet_historique.html.twig', [
            'trajetsAvenir'  => $avenir,
            'trajetsEnCours' => $encours,
            'trajetsPasses'  => $passes,
        ]);
    }

    // ==========================================================
    // 🔵 MODIFIER TRAJET
    // ==========================================================
    #[Route('/trajet/{id}/edit', name: 'app_trajet_edit')]
    public function edit(
        Trajet $trajet,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user || $trajet->getConducteur() !== $user) {
            $this->addFlash('danger', 'Tu ne peux modifier que tes trajets.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        $form = $this->createForm(TrajetEditType::class, $trajet, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Trajet modifié.');
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        return $this->render('trajet/edit.html.twig', [
            'trajet' => $trajet,
            'form'   => $form->createView(),
        ]);
    }

    // ==========================================================
    // ❌ ANNULER TRAJET (CONDUCTEUR) — CORRIGÉ
    // ==========================================================
    #[Route('/trajet/{id}/annuler-conducteur', name: 'trajet_annuler_conducteur', methods: ['POST'])]
    public function annulerTrajetConducteur(
        Trajet $trajet,
        EntityManagerInterface $em,
        MailerService $mailer
    ): Response {
        $user = $this->getUser();

        if (!$user || $trajet->getConducteur() !== $user) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('app_mes_trajets');
        }

        if ($trajet->getDateDepart() <= new \DateTime()) {
            $this->addFlash('danger', 'Le trajet a déjà commencé.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
        }

        $em->beginTransaction();

        try {
            // 🔁 Réservations
            $reservations = $em->getRepository(TrajetPassager::class)
                ->findBy(['trajet' => $trajet]);

            foreach ($reservations as $reservation) {
                $passager = $reservation->getPassager();

                if ($passager) {
                    $passager->setTokens($passager->getTokens() + 2);

                    $refund = new \App\Entity\TokenTransaction();
                    $refund->setUser($passager);
                    $refund->setAmount(2);
                    $refund->setType('CREDIT');
                    $refund->setReason('REFUND_ANNULATION_CONDUCTEUR');
                    $refund->setTrajetId($trajet->getId());
                    $em->persist($refund);
                }

                $em->remove($reservation);
            }

            // ✉️ MAILS AVANT SUPPRESSION (ID ENCORE VALIDE)
            $mailer->notifyCancellationByConducteur($trajet);

            // ❌ Suppression trajet
            $em->remove($trajet);
            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }

        $this->addFlash('info', 'Le trajet a été annulé.');
        return $this->redirectToRoute('app_mes_trajets');
    }

    // ==========================================================
    // 🧠 UTILITAIRE
    // ==========================================================
    private function classifyTrajet(
        Trajet $trajet,
        string $role,
        ?TrajetPassager $reservation,
        \DateTimeInterface $now,
        array &$avenir,
        array &$encours,
        array &$passes
    ): void {
        $item = [
            'trajet'      => $trajet,
            'role'        => $role,
            'reservation' => $reservation,
        ];

        if ($trajet->getDateDepart() > $now) {
            $avenir[] = $item;
        } elseif ($trajet->getDateArrivee() && $trajet->getDateArrivee() < $now) {
            $passes[] = $item;
        } else {
            $encours[] = $item;
        }
    }
}
