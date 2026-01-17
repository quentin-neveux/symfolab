<?php

namespace App\Entity;

use App\Repository\DisputeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Trajet;
use App\Entity\Review;

#[ORM\Entity(repositoryClass: DisputeRepository::class)]
class Dispute
{
    public const STATUS_OPEN      = 'OPEN';
    public const STATUS_IN_REVIEW = 'IN_REVIEW';
    public const STATUS_RESOLVED  = 'RESOLVED';
    public const STATUS_REJECTED  = 'REJECTED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 50)]
    private string $reason = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reporter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $target = null;

    // ✅ IMPORTANT: peut être NULL si le trajet a été supprimé
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Trajet $trajet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Review $review = null;

    #[ORM\Column(type: 'integer')]
    private int $reporterTokensPaid = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_OPEN;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [
            self::STATUS_OPEN,
            self::STATUS_IN_REVIEW,
            self::STATUS_RESOLVED,
            self::STATUS_REJECTED,
        ], true)) {
            throw new \InvalidArgumentException('Invalid dispute status');
        }

        $this->status = $status;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $msg = $message !== null ? trim($message) : null;
        $this->message = ($msg === '') ? null : $msg;
        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $user): self
    {
        if (!$user) {
            throw new \InvalidArgumentException('Reporter cannot be null');
        }

        $this->reporter = $user;
        return $this;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $user): self
    {
        if (!$user) {
            throw new \InvalidArgumentException('Target cannot be null');
        }

        $this->target = $user;
        return $this;
    }

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): self
    {
        $this->trajet = $trajet;
        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): self
    {
        $this->review = $review;
        return $this;
    }

    public function getReporterTokensPaid(): int
    {
        return $this->reporterTokensPaid;
    }

    public function setReporterTokensPaid(int $tokens): self
    {
        $this->reporterTokensPaid = max(0, $tokens);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}