<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    // ======================================================
    // 🔑 IDENTIFIANT
    // ======================================================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ======================================================
    // 🧭 INFORMATIONS DE TRAJET
    // ======================================================
    #[ORM\Column(length: 100)]
    private ?string $villeDepart = null;

    #[ORM\Column(length: 100)]
    private ?string $villeArrivee = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $duree = null;

    // ======================================================
    // 💰 TARIF
    // ======================================================
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $price = null;

    // ======================================================
    // 💰 VERSEMENT CONDUCTEUR (liée au trajet)
    // ======================================================
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $payoutAmount = '0.00';

    #[ORM\Column(length: 15, options: ['default' => 'PENDING'])]
    private string $payoutStatus = 'PENDING'; // PENDING | RELEASED | DISPUTED

    // ======================================================
    // 🚗 VÉHICULE ASSOCIÉ
    // ======================================================
    #[ORM\ManyToOne(inversedBy: 'trajets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    // ======================================================
    // 👥 PLACES DISPONIBLES
    // ======================================================
    #[ORM\Column(type: 'integer')]
    private int $placesDisponibles = 1;

    // ======================================================
    // 📝 COMMENTAIRE
    // ======================================================
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    // ======================================================
    // 👤 CONDUCTEUR
    // ======================================================
    #[ORM\ManyToOne(inversedBy: 'trajetsConduits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $conducteur = null;

    // ======================================================
    // 🧍 PASSAGERS
    // ======================================================
    #[ORM\OneToMany(
        mappedBy: 'trajet',
        targetEntity: TrajetPassager::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $passagers;

    // ======================================================
    // ✔ VALIDATION DE FIN
    // ======================================================
    #[ORM\Column(type: 'boolean')]
    private bool $conducteurConfirmeFin = false;

    // ======================================================
    // 🔧 CONSTRUCTEUR
    // ======================================================
    public function __construct()
    {
        $this->passagers = new ArrayCollection();
    }

    // ======================================================
    // GETTERS & SETTERS
    // ======================================================

    public function getId(): ?int { return $this->id; }

    public function getVilleDepart(): ?string { return $this->villeDepart; }
    public function setVilleDepart(string $villeDepart): self
    {
        $this->villeDepart = $villeDepart;
        return $this;
    }

    public function getVilleArrivee(): ?string { return $this->villeArrivee; }
    public function setVilleArrivee(string $villeArrivee): self
    {
        $this->villeArrivee = $villeArrivee;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface { return $this->dateDepart; }
    public function setDateDepart(\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface { return $this->dateArrivee; }
    public function setDateArrivee(?\DateTimeInterface $dateArrivee): self
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getDuree(): ?\DateTimeInterface { return $this->duree; }
    public function setDuree(?\DateTimeInterface $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(?float $price): self
    {
        $this->price = $price;

        // On initialise le montant à verser (si tu veux faire autrement plus tard, on changera ici)
        if ($price !== null) {
            $this->payoutAmount = number_format((float) $price, 2, '.', '');
        }

        return $this;
    }

    // Versement
    public function getPayoutAmount(): string
    {
        return $this->payoutAmount;
    }

    public function setPayoutAmount(string $amount): self
    {
        $this->payoutAmount = number_format((float) $amount, 2, '.', '');
        return $this;
    }

    public function getPayoutStatus(): string
    {
        return $this->payoutStatus;
    }

    public function setPayoutStatus(string $status): self
    {
        $this->payoutStatus = $status;
        return $this;
    }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getPlacesDisponibles(): int { return $this->placesDisponibles; }
    public function setPlacesDisponibles(int $places): self
    {
        $this->placesDisponibles = max(1, $places);
        return $this;
    }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    // 👤 CONDUCTEUR
    public function getConducteur(): ?User { return $this->conducteur; }
    public function setConducteur(User $conducteur): self
    {
        $this->conducteur = $conducteur;
        return $this;
    }

    // 🧍 PASSAGERS
    public function getPassagers(): Collection { return $this->passagers; }

    public function addPassager(TrajetPassager $passager): self
    {
        if (!$this->passagers->contains($passager)) {
            $this->passagers->add($passager);
            $passager->setTrajet($this);
        }
        return $this;
    }

    public function removePassager(TrajetPassager $passager): self
    {
        if ($this->passagers->removeElement($passager)) {
            if ($passager->getTrajet() === $this) {
                $passager->setTrajet(null);
            }
        }
        return $this;
    }

    // FIN
    public function isConducteurConfirmeFin(): bool
    {
        return $this->conducteurConfirmeFin;
    }

    public function setConducteurConfirmeFin(bool $value): self
    {
        $this->conducteurConfirmeFin = $value;
        return $this;
    }

    public function isFinished(): bool
    {
        $end = $this->dateArrivee ?? $this->dateDepart;
        return $end < new \DateTimeImmutable();
    }
}
