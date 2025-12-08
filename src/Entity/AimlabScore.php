<?php

namespace App\Entity;

use App\Repository\AimlabScoreRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: AimlabScoreRepository::class)]
class AimlabScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $averageTime;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $playedAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'aimlabScores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->playedAt = new \DateTime(); // auto timestamp
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAverageTime(): float
    {
        return $this->averageTime;
    }

    public function setAverageTime(float $averageTime): self
    {
        $this->averageTime = $averageTime;
        return $this;
    }

    public function getPlayedAt(): \DateTimeInterface
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeInterface $playedAt): self
    {
        $this->playedAt = $playedAt;
        return $this;
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
}
