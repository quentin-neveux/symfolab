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

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // =========================================================
    // 🧩 IDENTITÉ
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    // =========================================================
    // 🧠 AIMLAB
    // =========================================================

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $aimlabBestAvg = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AimlabScore::class, orphanRemoval: true)]
    private Collection $aimlabScores;

    // =========================================================
    // 🚗 PRÉFÉRENCES DE VOYAGE
    // =========================================================

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

    // =========================================================
    // 🔐 SÉCURITÉ
    // =========================================================

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // =========================================================
    // 💰 TOKENS
    // =========================================================

    #[ORM\Column(type: 'integer')]
    private int $tokens = 100;

    // =========================================================
    // 🔗 RELATIONS
    // =========================================================

    #[ORM\OneToMany(mappedBy: 'conducteur', targetEntity: Trajet::class)]
    private Collection $trajets;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviewsGiven;

    #[ORM\OneToMany(mappedBy: 'target', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviewsReceived;

    // =========================================================
    // 🔧 CONSTRUCTEUR
    // =========================================================

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->trajets = new ArrayCollection();
        $this->aimlabScores = new ArrayCollection();
        $this->reviewsGiven = new ArrayCollection();
        $this->reviewsReceived = new ArrayCollection();
    }

    // =========================================================
    // 🌟 GETTERS & SETTERS
    // =========================================================

    public function getId(): ?int { return $this->id; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = ucfirst(trim($prenom)); return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = strtoupper(trim($nom)); return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = strtolower(trim($email)); return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self { $this->photo = $photo; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): self { $this->bio = $bio; return $this; }

    // =========================================================
    // 🎮 AIMLAB
    // =========================================================

    public function getAimlabBestAvg(): ?float { return $this->aimlabBestAvg; }
    public function setAimlabBestAvg(?float $avg): self { $this->aimlabBestAvg = $avg; return $this; }

    public function getAimlabScores(): Collection { return $this->aimlabScores; }

    // =========================================================
    // 🚗 PREFERENCES
    // =========================================================

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

    // =========================================================
    // 🔐 SÉCURITÉ
    // =========================================================

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getUserIdentifier(): string { return (string)$this->email; }

    public function eraseCredentials(): void {}

    // =========================================================
    // 💰 TOKENS
    // =========================================================

    public function getTokens(): int
    {
        return $this->tokens;
    }

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
        if ($this->tokens < 0) { $this->tokens = 0; }
        return $this;
    }

    // =========================================================
    // 🔗 RELATIONS
    // =========================================================

    public function getTrajets(): Collection { return $this->trajets; }

    public function getReviewsReceived(): Collection { return $this->reviewsReceived; }

    public function addReviewsReceived(Review $review): self
    {
        if (!$this->reviewsReceived->contains($review)) {
            $this->reviewsReceived->add($review);
            $review->setTarget($this);
        }
        return $this;
    }

    public function getReviewsGiven(): Collection { return $this->reviewsGiven; }

    public function addReviewsGiven(Review $review): self
    {
        if (!$this->reviewsGiven->contains($review)) {
            $this->reviewsGiven->add($review);
            $review->setAuthor($this);
        }
        return $this;
    }

    // =========================================================
    // ⭐ OUTIL FACULTATIF
    // =========================================================

    public function getAverageRating(ReviewRepository $repo): ?float
    {
        return $repo->getAverageRatingForUser($this->id);
    }
}
