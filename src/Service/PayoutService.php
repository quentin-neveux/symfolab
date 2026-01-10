<?php

namespace App\Service;

use App\Entity\Dispute;
use App\Entity\TokenTransaction;
use App\Entity\Trajet;
use App\Repository\DisputeRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayoutService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DisputeRepository $disputeRepo,
    ) {}

    /**
     * Tente de payer le conducteur si les conditions sont OK.
     * Idempotent : si déjà payé, ne refait rien.
     */
    public function tryPayoutForTrajet(Trajet $trajet): void
    {
        // 1) conducteur ?
        $conducteur = $trajet->getConducteur();
        if (!$conducteur) {
            return;
        }

        // 2) déjà payé ? (évite double crédit)
        $already = $this->em->getRepository(TokenTransaction::class)->findOneBy([
            'reason' => 'TRIP_PAYOUT',
            'trajet' => $trajet, // si ton TokenTransaction n’a pas trajet, enlève ce critère
        ]);

        if ($already) {
            return;
        }

        // 3) si dispute active => on bloque
        $active = $this->disputeRepo->countActiveForTrajet($trajet->getId());
        if ($active > 0) {
            return;
        }

        // 4) si dispute existe et résolue "contre" le conducteur => on ne paye pas (règle actuelle)
        // (optionnel : uniquement si tu veux bloquer définitivement quand RESOLVED)
        $resolvedAgainst = $this->disputeRepo->countResolvedForTrajet($trajet->getId());
        if ($resolvedAgainst > 0) {
            return;
        }

        // 5) montant à payer (à adapter)
        // Exemple : conducteur gagne tokenCost * nbPassagers
        $nbPassagers = $trajet->getTrajetPassagers()?->count() ?? 0; // adapte si tu as un getter différent
        $amount = (int) ($trajet->getTokenCost() * $nbPassagers);

        if ($amount <= 0) {
            return;
        }

        // 6) crédit tokens + transaction
        $conducteur->setTokens($conducteur->getTokens() + $amount);

        $tx = new TokenTransaction();
        $tx->setUser($conducteur);
        $tx->setAmount($amount);
        $tx->setType('CREDIT');
        $tx->setReason('TRIP_PAYOUT');

        // si tu as un lien trajet dans TokenTransaction, fais-le :
        // $tx->setTrajet($trajet);

        $this->em->persist($tx);
        $this->em->flush();
    }
}
