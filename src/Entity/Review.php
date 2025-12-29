<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'reviewsGiven')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'reviewsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $target = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self { $this->comment = $comment; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): self { $this->author = $author; return $this; }

    public function getTarget(): ?User { return $this->target; }
    public function setTarget(?User $target): self { $this->target = $target; return $this; }

    public function getTrajet(): ?Trajet { return $this->trajet; }
    public function setTrajet(?Trajet $trajet): self { $this->trajet = $trajet; return $this; }
}
