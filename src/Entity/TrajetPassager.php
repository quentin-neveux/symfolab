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

        // auto-set de la date si paiement validé
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

    /**
     * Le passager peut noter uniquement si :
     * - le paiement a été validé
     * - le conducteur a confirmé la fin
     * - le passager a confirmé la fin
     * - aucune note n’a encore été laissée
     */
    public function peutNoter(): bool
    {
        return
            $this->isPaid &&
            $this->trajet !== null &&
            $this->trajet->isConducteurConfirmeFin() &&
            $this->passagerConfirmeFin &&
            !$this->aDejaNote;
    }
}
