<?php

namespace App\Entity;

use App\Repository\DisputeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisputeRepository::class)]
class Dispute
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_REVIEW = 'IN_REVIEW';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_REJECTED = 'REJECTED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
