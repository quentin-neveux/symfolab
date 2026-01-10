<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\TrajetPassager;
use App\Entity\Vehicle;
use App\Entity\TokenTransaction;
use App\Form\TrajetType;
use App\Form\TrajetEditType;
use App\Repository\DisputeRepository;
use App\Repository\ReviewRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TrajetController extends AbstractController
{
    use TargetPathTrait;

    // ==========================================================
    // 🟢 PROPOSER UN TRAJET
    // ==========================================================
    #[Route('/profil/proposer-trajet', name: 'app_proposer_trajet')]
    public function proposer(Request $request, EntityManagerInterface $em, MailerService $mailer): Response
    {
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

            if ($user->getTokens() < 2) {
                $this->addFlash('danger', 'Il te faut au moins 2 tokens pour publier un trajet.');
                return $this->redirectToRoute('app_mes_trajets');
            }

            $user->setTokens($user->getTokens() - 2);

            $tx = new TokenTransaction();
            $tx->setUser($user);
            $tx->setAmount(2);
            $tx->setType('DEBIT');
            $tx->setReason('CREATION_TRAJET');
            $tx->setTrajetId(null);
            $em->persist($tx);

            $em->persist($trajet);
            $em->flush();

            $mailer->notifyTrajetCreated($trajet);

            $this->addFlash('success', 'Ton trajet a bien été publié.');
            return $this->redirectToRoute('app_trajet_detail', [
                'id' => $trajet->getId()
            ]);
            
        }

        return $this->render('trajet/proposer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ==========================================================
    // 🔍 DÉTAIL TRAJET
    // ==========================================================
    #[Route('/trajet/{id}', name: 'app_trajet_detail')]
    public function detail(Trajet $trajet, EntityManagerInterface $em, ReviewRepository $reviewRepo): Response
    {
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
    // 📜 HISTORIQUE
    // ==========================================================
    #[Route('/trajet_historique', name: 'trajet_historique')]
    public function trajetHistorique(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_connexion');
        }

        $now = new \DateTimeImmutable();
        $limit = $now->modify('+1 hour');

        $trajetRepo = $em->getRepository(Trajet::class);
        $tpRepo     = $em->getRepository(TrajetPassager::class);

        // A VENIR
        $trajetsAvenirConducteur = $trajetRepo->createQueryBuilder('t')
            ->andWhere('t.conducteur = :u')
            ->andWhere('t.dateDepart > :limit')
            ->setParameter('u', $user)
            ->setParameter('limit', $limit)
            ->getQuery()->getResult();

        $reservationsAvenirPassager = $tpRepo->createQueryBuilder('tp')
            ->leftJoin('tp.trajet', 't')->addSelect('t')
            ->andWhere('tp.passager = :u')
            ->andWhere('t.dateDepart > :limit')
            ->setParameter('u', $user)
            ->setParameter('limit', $limit)
            ->getQuery()->getResult();

        // EN COURS
        $trajetsAConfirmerConducteur = $trajetRepo->createQueryBuilder('t')
            ->andWhere('t.conducteur = :u')
            ->andWhere('t.dateDepart <= :limit')
            ->andWhere('t.dateArrivee IS NULL')
            ->andWhere('(t.conducteurConfirmeFin = false OR t.conducteurConfirmeFin IS NULL)')
            ->setParameter('u', $user)
            ->setParameter('limit', (new \DateTimeImmutable())->modify('+1 hour'))
            ->orderBy('t.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();
         

        $reservationsAConfirmerPassager = $tpRepo->createQueryBuilder('tp')
            ->leftJoin('tp.trajet', 't')
            ->addSelect('t')
            ->andWhere('tp.passager = :u')
            ->andWhere('t.dateDepart <= :limit')
            ->andWhere('t.dateArrivee IS NULL')
            ->andWhere('(tp.passagerConfirmeFin = false OR tp.passagerConfirmeFin IS NULL)')
            ->setParameter('u', $user)
            ->setParameter('limit', (new \DateTimeImmutable())->modify('+1 hour'))
            ->orderBy('t.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();

        // PASSES
        $trajetsPassesConducteur = $trajetRepo->createQueryBuilder('t')
            ->andWhere('t.conducteur = :u')
            ->andWhere('t.dateArrivee IS NOT NULL OR t.conducteurConfirmeFin = true')
            ->setParameter('u', $user)
            ->getQuery()->getResult();

        $reservationsPassesPassager = $tpRepo->createQueryBuilder('tp')
            ->leftJoin('tp.trajet', 't')->addSelect('t')
            ->andWhere('tp.passager = :u')
            ->andWhere('t.dateArrivee IS NOT NULL OR tp.passagerConfirmeFin = true')
            ->setParameter('u', $user)
            ->getQuery()->getResult();

// =========================
// FUSION
// =========================

$trajetsAvenir = [];
foreach ($trajetsAvenirConducteur as $t) {
    $trajetsAvenir[] = $this->flattenTrajetForView($t, 'conducteur', null);
}
foreach ($reservationsAvenirPassager as $r) {
    $t = $r->getTrajet();
    if ($t) {
        $trajetsAvenir[] = $this->flattenTrajetForView($t, 'passager', $r);
    }
}

$trajetsAConfirmer = [];
foreach ($trajetsAConfirmerConducteur as $t) {
    $trajetsAConfirmer[] = $this->flattenTrajetForView($t, 'conducteur', null);
}
foreach ($reservationsAConfirmerPassager as $r) {
    $t = $r->getTrajet();
    if ($t) {
        $trajetsAConfirmer[] = $this->flattenTrajetForView($t, 'passager', $r);
    }
}

$historiques = [];
foreach ($trajetsPassesConducteur as $t) {
    $historiques[] = $this->flattenTrajetForView($t, 'conducteur', null);
}
foreach ($reservationsPassesPassager as $r) {
    $t = $r->getTrajet();
    if ($t) {
        $historiques[] = $this->flattenTrajetForView($t, 'passager', $r);
    }
}

return $this->render('historique/historique.html.twig', [
    'trajetsAvenir'     => $trajetsAvenir,
    'trajetsAConfirmer' => $trajetsAConfirmer,
    'historiques'       => $historiques,
    '_marker'           => 'MARKER_TRAJET_HISTORIQUE_001',
]);
    }

    // ==========================================================
    // 🔧 FLATTEN
    // ==========================================================
    private function flattenTrajetForView(Trajet $trajet, string $role, ?TrajetPassager $reservation): array
{
    $dateDepart = $trajet->getDateDepart();

    // aDejaNote : supporte isADejaNote() ou getADejaNote()
    $aDejaNote = false;
    if ($reservation) {
        if (method_exists($reservation, 'isADejaNote')) {
            $aDejaNote = (bool) $reservation->isADejaNote();
        } elseif (method_exists($reservation, 'getADejaNote')) {
            $aDejaNote = (bool) $reservation->getADejaNote();
        }
    }

    return [
        'id' => (int) $trajet->getId(),

        'villeDepart'  => (string) ($trajet->getVilleDepart() ?? ''),
        'villeArrivee' => (string) ($trajet->getVilleArrivee() ?? ''),
        'tokenCost'    => (int) ($trajet->getTokenCost() ?? 0),

        'dateDepart'   => $dateDepart,
        'dateDepartTs' => $dateDepart ? $dateDepart->getTimestamp() : 0,

        'role' => $role,

        'conducteurConfirmeFin' => (bool) $trajet->isConducteurConfirmeFin(),
        'passagerConfirmeFin'   => $reservation ? (bool) $reservation->isPassagerConfirmeFin() : false,

        // ✅ indispensable pour le bouton "Confirmer" (route trajet_passager_confirmer_fin)
        'reservationId' => $reservation?->getId(),

    'aDejaNote' => $aDejaNote,
];
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
    // ❌ ANNULER TRAJET (CONDUCTEUR)
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
            return $this->redirectToRoute('app_trajet_detail', ['id' => $trajet->getId()]);
        }

        $em->beginTransaction();

        try {
            $reservations = $em->getRepository(TrajetPassager::class)
                ->findBy(['trajet' => $trajet]);

            foreach ($reservations as $reservation) {
                $passager = $reservation->getPassager();

                if ($passager) {
                    $passager->setTokens($passager->getTokens() + 2);

                    $refund = new TokenTransaction();
                    $refund->setUser($passager);
                    $refund->setAmount(2);
                    $refund->setType('CREDIT');
                    $refund->setReason('REFUND_ANNULATION_CONDUCTEUR');
                    $refund->setTrajetId($trajet->getId());
                    $em->persist($refund);
                }

                $em->remove($reservation);
            }

            $mailer->notifyCancellationByConducteur($trajet);

            $em->remove($trajet);
            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }

        $this->addFlash('info', 'Le trajet a été annulé.');
        return $this->redirectToRoute('trajet_historique');
    }

    // ==========================================================
    // 🏁 ARRIVÉE À DESTINATION (CONDUCTEUR)
    // ==========================================================
    #[Route('/trajet/{id}/arrivee', name: 'trajet_arrivee', methods: ['POST'])]
    public function arriveeDestination(
        Trajet $trajet,
        EntityManagerInterface $em,
        MailerService $mailer
    ): Response {
        $user = $this->getUser();

        if (!$user || $trajet->getConducteur() !== $user) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('trajet_historique');
        }

        $trajet->setConducteurConfirmeFin(true);
        $em->flush();

        // $mailer->notifyTrajetClosedToPassengers($trajet);

        $this->addFlash('success', 'Trajet marqué comme terminé. Les passagers peuvent maintenant confirmer la fin.');
        return $this->redirectToRoute('trajet_historique');
    }

    // ==========================================================
// ✅ CONFIRMATION FIN DE TRAJET (PASSAGER) + PAYOUT CONDUCTEUR
// ==========================================================
#[Route('/trajet-passager/{id}/confirmer-fin', name: 'trajet_passager_confirmer_fin', methods: ['POST'])]
public function confirmerFinPassager(
    TrajetPassager $reservation,
    EntityManagerInterface $em,
    DisputeRepository $disputeRepo,
    MailerService $mailer
): Response {

    error_log('[CONFIRM_FIN] START tp=' . $reservation->getId());

    $user = $this->getUser();

    if (!$user || $reservation->getPassager() !== $user) {
        $this->addFlash('danger', 'Action non autorisée.');
        return $this->redirectToRoute('trajet_historique');
    }

    if ($reservation->isPassagerConfirmeFin()) {
        $this->addFlash('info', 'Tu as déjà confirmé ce trajet.');
        return $this->redirectToRoute('trajet_historique');
    }

    $reservation->setPassagerConfirmeFin(true);

    $trajet = $reservation->getTrajet();
    $chauffeur = $trajet?->getConducteur();

    // sécurité
    if (!$trajet || !$chauffeur) {
        $em->flush();
        $this->addFlash('success', 'Merci, ta confirmation a bien été prise en compte.');
        return $this->redirectToRoute('trajet_historique');
    }

    if (!$reservation->isPaid()) {
        $reservation->setIsPaid(true);
        $reservation->setPaidAt(new \DateTimeImmutable());
    }

    $trajetId = (int) $trajet->getId();
    $amount   = max(0, (int) $trajet->getTokenCost());
    $reason   = 'PAYOUT_TP_' . $reservation->getId();

    $txRepo = $em->getRepository(TokenTransaction::class);

    $already = $txRepo->findOneBy([
        'reason'   => $reason,
        'trajetId' => $trajetId,
    ]);

    if (!$already && $amount > 0) {

        $hasActiveDispute = method_exists($disputeRepo, 'countActiveForTrajet')
            ? ((int) $disputeRepo->countActiveForTrajet($trajetId) > 0)
            : false;

        $tx = new TokenTransaction();
        $tx->setUser($chauffeur);
        $tx->setAmount($amount);
        $tx->setReason($reason);
        $tx->setTrajetId($trajetId);

        if ($hasActiveDispute) {
            $tx->setType('PENDING');
        } else {
            $tx->setType('CREDIT');
            $chauffeur->setTokens($chauffeur->getTokens() + $amount);
        }

        $em->persist($tx);
    }

    $em->flush();
    error_log('[CONFIRM_FIN] AFTER_FLUSH tp=' . $reservation->getId());

    // ✅ IMPORTANT : l’appel mail doit être après flush + sans entité doctrine
    if (!$already && $amount > 0) {
        $hasActiveDispute = method_exists($disputeRepo, 'countActiveForTrajet')
            ? ((int) $disputeRepo->countActiveForTrajet($trajetId) > 0)
            : false;

        if (!$hasActiveDispute) {
            // ⚠️ on envoie que des scalaires pour éviter lazy-load / recursion
            $mailer->notifyPayoutReleased($trajet, $amount);
        }
    }

    error_log('[CONFIRM_FIN] BEFORE_REDIRECT tp=' . $reservation->getId());

    $this->addFlash('success', 'Merci, ta confirmation a bien été prise en compte.');
    return $this->redirectToRoute('trajet_historique');
}
}