<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "ville_depart", length: 100)]
    private ?string $villeDepart = null;

    #[ORM\Column(name: "ville_arrivee", length: 100)]
    private ?string $villeArrivee = null;

    #[ORM\Column(name: "date_depart", type: 'datetime')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: "date_arrivee", type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $duree = null;

    #[ORM\Column(name: "places_disponibles")]
    private ?int $placesDisponibles = null;

    #[ORM\Column(type: 'integer')]
    private ?int $tokenCost = 10;

    #[ORM\Column(name: "type_vehicule", length: 100, nullable: true)]
    private ?string $typeVehicule = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $energie = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estEcologique = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'trajets')]
    private ?User $conducteur = null;

    #[ORM\OneToMany(mappedBy: 'trajet', targetEntity: TrajetPassager::class, cascade: ['persist', 'remove'])]
    private Collection $passagers;

    #[ORM\Column(type: 'boolean')]
    private bool $conducteurConfirmeFin = false;

    public function __construct()
    {
        $this->passagers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVilleDepart(): ?string
    {
        return $this->villeDepart;
    }

    public function setVilleDepart(string $villeDepart): self
    {
        $this->villeDepart = $villeDepart;
        return $this;
    }

    public function getVilleArrivee(): ?string
    {
        return $this->villeArrivee;
    }

    public function setVilleArrivee(string $villeArrivee): self
    {
        $this->villeArrivee = $villeArrivee;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(?\DateTimeInterface $dateArrivee): self
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getDuree(): ?\DateTimeInterface
    {
        return $this->duree;
    }

    public function setDuree(?\DateTimeInterface $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    public function getPlacesDisponibles(): ?int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(int $placesDisponibles): self
    {
        $this->placesDisponibles = $placesDisponibles;
        return $this;
    }

    public function getTokenCost(): ?int
    {
        return $this->tokenCost;
    }

    public function setTokenCost(int $tokenCost): self
    {
        $this->tokenCost = $tokenCost;
        return $this;
    }

    public function getTypeVehicule(): ?string
    {
        return $this->typeVehicule;
    }

    public function setTypeVehicule(?string $typeVehicule): self
    {
        $this->typeVehicule = $typeVehicule;
        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(?string $energie): self
    {
        $this->energie = $energie;
        return $this;
    }

    public function isEstEcologique(): bool
    {
        return $this->estEcologique;
    }

    public function setEstEcologique(bool $value): self
    {
        $this->estEcologique = $value;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getConducteur(): ?User
    {
        return $this->conducteur;
    }

    public function setConducteur(?User $conducteur): self
    {
        $this->conducteur = $conducteur;
        return $this;
    }

    /** @return Collection<int, TrajetPassager> */
    public function getPassagers(): Collection
    {
        return $this->passagers;
    }

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

    public function isConducteurConfirmeFin(): bool
    {
        return $this->conducteurConfirmeFin;
    }

    public function setConducteurConfirmeFin(bool $value): self
    {
        $this->conducteurConfirmeFin = $value;
        return $this;
    }
    public function calculateTokenCost(): self
{
    // Exemple simple
    $distance = $this->estimateDistance($this->villeDepart, $this->villeArrivee);

    if ($distance < 50)      $this->tokenCost = 5;
    elseif ($distance < 150) $this->tokenCost = 10;
    elseif ($distance < 300) $this->tokenCost = 20;
    else                     $this->tokenCost = 30;

    return $this;
}
private function estimateDistance(string $from, string $to): int
{
    $from = $this->normalizeCity($from);
    $to   = $this->normalizeCity($to);

    $distances = [
        // FRANCE
        'paris' => [
            'lyon' => 465, 'marseille' => 775, 'lille' => 225,
            'bordeaux' => 590, 'strasbourg' => 490, 'toulouse' => 680
        ],
        'lyon' => [
            'paris' => 465, 'marseille' => 315, 'geneve' => 150
        ],

        // SUISSE
        'geneve' => [
            'lausanne' => 65, 'zurich' => 280, 'lyon' => 150
        ],
        'zurich' => [
            'geneve' => 280, 'lausanne' => 215
        ],

        // ITALIE
        'milan' => [
            'turin' => 145, 'venise' => 270, 'geneve' => 320
        ],
        'rome' => [
            'naples' => 225, 'florence' => 275
        ],

        // BELGIQUE
        'bruxelles' => [
            'liege' => 100, 'lille' => 120, 'paris' => 300
        ],
    ];

    if (isset($distances[$from][$to])) {
        return $distances[$from][$to];
    }

    // fallback
    return 100;
}


private function normalizeCity(string $city): string
{
    return strtolower(
        preg_replace('/[^a-z]/', '', 
            iconv('UTF-8', 'ASCII//TRANSLIT', $city)
        )
    );
}



}
