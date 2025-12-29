<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use App\Entity\User;
use App\Entity\Trajet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $marque = null;

    #[ORM\Column(length: 50)]
    private ?string $modele = null;

    #[ORM\Column(length: 20)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 20)]
    private ?string $energie = null;

    #[ORM\Column(type: 'integer')]
    private int $places = 4;

    #[ORM\Column(type: 'boolean')]
    private bool $isEco = false;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $type = null;

    // ⭐ DATE D'IMMATRICULATION DU VÉHICULE
    #[ORM\Column(name: 'date_immatriculation', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateImmatriculation = null;

    // ⭐ PROPRIÉTAIRE DU VÉHICULE
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    // ⭐ TRAJETS ASSOCIÉS
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Trajet::class)]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): self
    {
        $this->marque = $marque;
        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): self
    {
        $this->modele = $modele;
        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): self
    {
        $this->energie = $energie;
        return $this;
    }

    public function getPlaces(): int
    {
        return $this->places;
    }

    public function setPlaces(int $places): self
    {
        $this->places = $places;
        return $this;
    }

    public function isEco(): bool
    {
        return $this->isEco;
    }

    public function setIsEco(bool $isEco): self
    {
        $this->isEco = $isEco;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDateImmatriculation(): ?\DateTimeInterface
    {
        return $this->dateImmatriculation;
    }

    public function setDateImmatriculation(?\DateTimeInterface $dateImmatriculation): self
    {
        $this->dateImmatriculation = $dateImmatriculation;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): self
    {
        if (!$this->trajets->contains($trajet)) {
            $this->trajets->add($trajet);
            $trajet->setVehicle($this);
        }

        return $this;
    }

    public function removeTrajet(Trajet $trajet): self
    {
        if ($this->trajets->removeElement($trajet)) {
            if ($trajet->getVehicle() === $this) {
                $trajet->setVehicle(null);
            }
        }

        return $this;
    }
}
