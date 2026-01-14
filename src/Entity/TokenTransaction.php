<?php

namespace App\Entity;

use App\Repository\TokenTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenTransactionRepository::class)]
class TokenTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🔗 Utilisateur concerné (null possible pour opérations plateforme)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    // 🔢 Nombre de tokens
    #[ORM\Column(type: 'integer')]
    private int $amount = 0;

    /**
     * Type de transaction :
     * - DEBIT
     * - CREDIT
     */
    #[ORM\Column(length: 20)]
    private string $type = 'DEBIT';

    // 🧠 Raison métier (RESERVATION, PAYMENT, ADMIN, REFUND, etc.)
    #[ORM\Column(length: 50)]
    private string $reason = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // 🔗 Optionnel : trajet concerné
    #[ORM\Column(nullable: true)]
    private ?int $trajetId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // =====================
    // GETTERS / SETTERS
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // ✅ Ajouté : utile si tu veux forcer une date (et évite ton 500)
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTrajetId(): ?int
    {
        return $this->trajetId;
    }

    public function setTrajetId(?int $trajetId): self
    {
        $this->trajetId = $trajetId;
        return $this;
    }
}
