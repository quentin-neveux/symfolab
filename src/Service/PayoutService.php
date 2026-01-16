<?php

namespace App\Service;

use App\Entity\TokenTransaction;
use App\Entity\Trajet;
use App\Repository\DisputeRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayoutService
{
    public const REASON_TRIP_PAYOUT = 'TRIP_PAYOUT';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DisputeRepository $disputeRepo,
        private readonly MailerService $mailer,
    ) {}

    /**
     * Paiement conducteur en fin de trajet.
     * Règle métier : payout UNIQUEMENT si
     * - conducteur a confirmé la fin
     * - et tous les passagers (réservations payées) ont confirmé la fin
     * Idempotent : si déjà payé, ne refait rien.
     */
    public function tryPayoutForTrajet(Trajet $trajet): void
    {
        $trajetId = (int) $trajet->getId();
        if ($trajetId <= 0) {
            return;
        }

        $conducteur = $trajet->getConducteur();
        if (!$conducteur) {
            return;
        }

        // 1) Condition métier : conducteur confirme la fin
        if (!$trajet->isConducteurConfirmeFin()) {
            return;
        }

        // 2) Condition métier : tous les passagers payés doivent avoir confirmé la fin
        foreach ($trajet->getPassagers() as $reservation) {
            if (!$reservation->isPaid()) {
                continue;
            }
            if (!$reservation->isPassagerConfirmeFin()) {
                return;
            }
        }

        // 3) Idempotence : déjà payé ?
        $already = $this->em->getRepository(TokenTransaction::class)->findOneBy([
            'reason'   => self::REASON_TRIP_PAYOUT,
            'trajetId' => $trajetId,
            'type'     => 'CREDIT',
        ]);
        if ($already) {
            return;
        }

        // 4) Disputes : si active => on bloque et on marque DISPUTED
        $active = (int) $this->disputeRepo->countActiveForTrajet($trajetId);
        if ($active > 0) {
            $trajet->setPayoutStatus('DISPUTED');
            $this->em->flush();
            return;
        }

        // 5) Si résolue "contre" conducteur => pas de payout (règle actuelle)
        $resolvedAgainst = (int) $this->disputeRepo->countResolvedForTrajet($trajetId);
        if ($resolvedAgainst > 0) {
            return;
        }

        // 6) Montant = somme des tokenCostCharged des réservations payées (sans frais plateforme)
        $amount = 0;
        foreach ($trajet->getPassagers() as $reservation) {
            if (!$reservation->isPaid()) {
                continue;
            }
            $amount += (int) $reservation->getTokenCostCharged();
        }

        if ($amount <= 0) {
            return;
        }

        // 7) Transaction atomique : crédit + trace + statut trajet
        $this->em->beginTransaction();

        try {
            $conducteur->setTokens($conducteur->getTokens() + $amount);

            $tx = new TokenTransaction();
            $tx->setUser($conducteur);
            $tx->setAmount($amount);
            $tx->setType('CREDIT');
            $tx->setReason(self::REASON_TRIP_PAYOUT);
            $tx->setTrajetId($trajetId);

            $trajet->setPayoutStatus('RELEASED');
            $trajet->setPayoutAmount((string) $amount);

            $this->em->persist($tx);
            $this->em->flush();
            $this->em->commit();

        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }

        // 8) Mails après commit
        $this->mailer->notifyPayoutReleased($trajet, $amount);
        $this->mailer->notifyPayoutReleasedToPassengers($trajet, $amount);
    }
}
