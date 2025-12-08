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

    #[ORM\ManyToOne]
    private ?User $user = null;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 50)]
    private string $type; // 'payment', 'refund', 'admin_add', 'auto_refund'

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(nullable: true)]
    private ?int $trajetId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // getters/setters...
}
