<?php

namespace App\Entity;

use App\Repository\TrajetPassagerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetPassagerRepository::class)]
class TrajetPassager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🔗 Trajet réservé
    #[ORM\ManyToOne(inversedBy: 'passagers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet = null;

    // 🔗 Passager concerné
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passager = null;

    // 💰 Paiement effectué ?
    #[ORM\Column(type: 'boolean')]
    private bool $isPaid = false;

    // 🟢 Le passager a confirmé la fin ?
    #[ORM\Column(type: 'boolean')]
    private bool $passagerConfirmeFin = false;

    // ⭐ Note déjà laissée ?
    #[ORM\Column(type: 'boolean')]
    private bool $aDejaNote = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    // =============================
    // RELATIONS
    // =============================
    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): self
    {
        $this->trajet = $trajet;
        return $this;
    }

    public function getPassager(): ?User
    {
        return $this->passager;
    }

    public function setPassager(?User $passager): self
    {
        $this->passager = $passager;
        return $this;
    }

    // =============================
    // PAIEMENT
    // =============================
    public function isPaid(): bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $paid): self
    {
        $this->isPaid = $paid;
        return $this;
    }

    // =============================
    // FIN DE TRAJET (passager)
    // =============================
    public function isPassagerConfirmeFin(): bool
    {
        return $this->passagerConfirmeFin;
    }

    public function setPassagerConfirmeFin(bool $value): self
    {
        $this->passagerConfirmeFin = $value;
        return $this;
    }

    // =============================
    // NOTATION
    // =============================
    public function isADejaNote(): bool
    {
        return $this->aDejaNote;
    }

    public function setADejaNote(bool $value): self
    {
        $this->aDejaNote = $value;
        return $this;
    }

    // =============================
    // LOGIQUE : Peut-il noter ?
    // =============================
    public function peutNoter(Trajet $trajet): bool
    {
        return
            $this->isPaid &&
            $trajet->isConducteurConfirmeFin() &&
            $this->passagerConfirmeFin &&
            !$this->aDejaNote;
    }
}
