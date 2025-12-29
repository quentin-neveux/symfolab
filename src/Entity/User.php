<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Repository\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Trajet;
use App\Entity\AimlabScore;
use App\Entity\Review;
use App\Entity\Vehicle;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ======================================================
    // 🔑 IDENTIFIANT
    // ======================================================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ======================================================
    // 👤 IDENTITÉ
    // ======================================================
    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    // ======================================================
    // 📷 PROFIL
    // ======================================================
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $aimlabBestAvg = null;

    // ======================================================
    // 🎮 AIMLAB
    // ======================================================
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AimlabScore::class, orphanRemoval: true)]
    private Collection $aimlabScores;

    // ======================================================
    // ⚙ PRÉFÉRENCES
    // ======================================================
    #[ORM\Column(length: 15, options: ["default" => "indifferent"])]
    private string $musique = 'indifferent';

    #[ORM\Column(length: 15, options: ["default" => "indifferent"])]
    private string $discussion = 'indifferent';

    #[ORM\Column(length: 15, options: ["default" => "indifferent"])]
    private string $animaux = 'indifferent';

    #[ORM\Column(length: 15, options: ["default" => "indifferent"])]
    private string $pausesCafe = 'indifferent';

    #[ORM\Column(length: 15, options: ["default" => "indifferent"])]
    private string $fumeur = 'indifferent';

    // ======================================================
    // 🔐 SÉCURITÉ
    // ======================================================
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // ======================================================
    // 🪙 TOKENS
    // ======================================================
    #[ORM\Column(type: 'integer')]
    private int $tokens = 20;

    // ======================================================
    // 💰 GAINS CONDUCTEUR (CUMULATIF)
    // ======================================================
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $earnings = '0.00';

    // ======================================================
    // 🚗 TRAJETS CONDUITS
    // ======================================================
    #[ORM\OneToMany(mappedBy: 'conducteur', targetEntity: Trajet::class)]
    private Collection $trajetsConduits;

    // ======================================================
    // ⭐ AVIS
    // ======================================================
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviewsGiven;

    #[ORM\OneToMany(mappedBy: 'target', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviewsReceived;

    // ======================================================
    // 🚘 VÉHICULES
    // ======================================================
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Vehicle::class, cascade: ['persist', 'remove'])]
    private Collection $vehicles;

    // ======================================================
    // 🔧 CONSTRUCTEUR
    // ======================================================
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->trajetsConduits = new ArrayCollection();
        $this->aimlabScores = new ArrayCollection();
        $this->reviewsGiven = new ArrayCollection();
        $this->reviewsReceived = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
    }

    // ======================================================
    // GETTERS / SETTERS
    // ======================================================

    public function getId(): ?int { return $this->id; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self
    {
        $this->prenom = ucfirst(trim($prenom));
        return $this;
    }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self
    {
        $this->nom = strtoupper(trim($nom));
        return $this;
    }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getAimlabBestAvg(): ?float { return $this->aimlabBestAvg; }
    public function setAimlabBestAvg(?float $avg): self
    {
        $this->aimlabBestAvg = $avg;
        return $this;
    }

    public function getAimlabScores(): Collection
    {
        return $this->aimlabScores;
    }

    // Préférences
    public function getMusique(): string { return $this->musique; }
    public function setMusique(string $musique): self { $this->musique = $musique; return $this; }

    public function getDiscussion(): string { return $this->discussion; }
    public function setDiscussion(string $discussion): self { $this->discussion = $discussion; return $this; }

    public function getAnimaux(): string { return $this->animaux; }
    public function setAnimaux(string $animaux): self { $this->animaux = $animaux; return $this; }

    public function getPausesCafe(): string { return $this->pausesCafe; }
    public function setPausesCafe(string $pausesCafe): self { $this->pausesCafe = $pausesCafe; return $this; }

    public function getFumeur(): string { return $this->fumeur; }
    public function setFumeur(string $fumeur): self { $this->fumeur = $fumeur; return $this; }

    // Sécurité
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void {}

    // Tokens
    public function getTokens(): int { return $this->tokens; }
    public function setTokens(int $tokens): self
    {
        $this->tokens = max(0, $tokens);
        return $this;
    }

    public function addTokens(int $amount): self
    {
        $this->tokens += max(0, $amount);
        return $this;
    }

    public function removeTokens(int $amount): self
    {
        $this->tokens -= max(0, $amount);
        if ($this->tokens < 0) {
            $this->tokens = 0;
        }
        return $this;
    }

    // 💰 Gains conducteur
    public function getEarnings(): string
    {
        return $this->earnings;
    }

    public function setEarnings(string $earnings): self
    {
        // Normalisation "2 décimales"
        $this->earnings = number_format((float) $earnings, 2, '.', '');
        return $this;
    }

    public function addEarnings(string $amount): self
    {
        $new = (float) $this->earnings + (float) $amount;
        $this->earnings = number_format($new, 2, '.', '');
        return $this;
    }

    // 🚗 Trajets conduits
    public function getTrajetsConduits(): Collection
    {
        return $this->trajetsConduits;
    }

    // ⭐ Avis
    public function getReviewsReceived(): Collection
    {
        return $this->reviewsReceived;
    }

    public function getReviewsGiven(): Collection
    {
        return $this->reviewsGiven;
    }

    // 🚘 Véhicules
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setOwner($this);
        }
        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->removeElement($vehicle)) {
            if ($vehicle->getOwner() === $this) {
                $vehicle->setOwner(null);
            }
        }
        return $this;
    }

    // ⭐ Moyenne des avis
    public function getAverageRating(ReviewRepository $repo): ?float
    {
        return $repo->getAverageRatingForUser($this->id);
    }
}
