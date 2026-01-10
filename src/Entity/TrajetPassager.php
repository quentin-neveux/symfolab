<?php

namespace App\Entity;

use App\Repository\TrajetPassagerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetPassagerRepository::class)]
#[ORM\Table(
    name: 'trajet_passager',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_trajet_passager',
            columns: ['trajet_id', 'passager_id']
        )
    ]
)]
class TrajetPassager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================
    // 🔗 RELATIONS
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'passagers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passager = null;

    // =========================================================
    // 💳 PAIEMENT
    // =========================================================

    #[ORM\Column(type: 'boolean')]
    private bool $isPaid = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isAuthorized = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    // =========================================================
    // 🪙 DÉTAIL DU DÉBIT TOKENS (snapshot)
    // =========================================================

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $tokenCostCharged = 0; // coût du trajet débité au moment du paiement

    #[ORM\Column(type: 'integer', options: ['default' => 2])]
    private int $platformFeeCharged = Trajet::PLATFORM_FEE_TOKENS; // fee plateforme (2)

    // =========================================================
    // 🏁 FIN DE TRAJET / NOTATION
    // =========================================================

    #[ORM\Column(type: 'boolean')]
    private bool $passagerConfirmeFin = false;

    #[ORM\Column(type: 'boolean')]
    private bool $aDejaNote = false;

    // =========================================================
    // GETTERS / SETTERS
    // =========================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(Trajet $trajet): self
    {
        $this->trajet = $trajet;
        return $this;
    }

    public function getPassager(): ?User
    {
        return $this->passager;
    }

    public function setPassager(User $passager): self
    {
        $this->passager = $passager;
        return $this;
    }

    // =========================================================
    // 💳 ÉTAT DU PAIEMENT
    // =========================================================

    public function isPaid(): bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $paid): self
    {
        $this->isPaid = $paid;

        if ($paid && $this->paidAt === null) {
            $this->paidAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isAuthorized(): bool
    {
        return $this->isAuthorized;
    }

    public function setIsAuthorized(bool $authorized): self
    {
        $this->isAuthorized = $authorized;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    // =========================================================
    // 🪙 TOKENS CHARGÉS
    // =========================================================

    public function getTokenCostCharged(): int
    {
        return $this->tokenCostCharged;
    }

    public function setTokenCostCharged(int $amount): self
    {
        $this->tokenCostCharged = max(0, $amount);
        return $this;
    }

    public function getPlatformFeeCharged(): int
    {
        return $this->platformFeeCharged;
    }

    public function setPlatformFeeCharged(int $amount): self
    {
        $this->platformFeeCharged = max(0, $amount);
        return $this;
    }

    public function getTotalTokensCharged(): int
    {
        return $this->tokenCostCharged + $this->platformFeeCharged;
    }

    /**
     * À appeler au moment du paiement (snapshot du coût du trajet).
     */
    public function snapshotCostsFromTrajet(): self
    {
        if (!$this->trajet) {
            throw new \RuntimeException('Trajet manquant pour snapshot des coûts.');
        }

        $this->tokenCostCharged = max(0, $this->trajet->getTokenCost());
        $this->platformFeeCharged = Trajet::PLATFORM_FEE_TOKENS;

        return $this;
    }

    // =========================================================
    // 🏁 FIN DE TRAJET
    // =========================================================

    public function isPassagerConfirmeFin(): bool
    {
        return $this->passagerConfirmeFin;
    }

    public function setPassagerConfirmeFin(bool $value): self
    {
        $this->passagerConfirmeFin = $value;
        return $this;
    }

    // =========================================================
    // ⭐ NOTATION
    // =========================================================

    public function isADejaNote(): bool
    {
        return $this->aDejaNote;
    }

    public function setADejaNote(bool $value): self
    {
        $this->aDejaNote = $value;
        return $this;
    }

    // =========================================================
    // 🧠 LOGIQUE MÉTIER
    // =========================================================

    public function peutNoter(): bool
{
    // déjà noté => non
    if ($this->isADejaNote()) {
        return false;
    }

    // doit être payé => oui
    if (!$this->isPaid()) {
        return false;
    }

    $trajet = $this->getTrajet();
    if (!$trajet) {
        return false;
    }

    // fin confirmée côté passager
    if (!$this->isPassagerConfirmeFin()) {
        return false;
    }

    // fin confirmée côté conducteur OU flag finished
    $conducteurOk = method_exists($trajet, 'isConducteurConfirmeFin') && $trajet->isConducteurConfirmeFin();
    $finishedOk   = method_exists($trajet, 'isFinished') && $trajet->isFinished();

    if (!$conducteurOk && !$finishedOk) {
        return false;
    }
    return true;
}
}

