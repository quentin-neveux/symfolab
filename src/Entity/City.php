<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ORM\Index(columns: ['name'], name: 'idx_city_name')]
#[ORM\Index(columns: ['country_code'], name: 'idx_city_country')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $name;

    #[ORM\Column(length: 200)]
    private string $nameAscii;

    #[ORM\Column(length: 2)]
    private string $countryCode;

    #[ORM\Column(nullable: true)]
    private ?int $population = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $lat;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $lon;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getNameAscii(): string { return $this->nameAscii; }
    public function setNameAscii(string $nameAscii): self { $this->nameAscii = $nameAscii; return $this; }

    public function getCountryCode(): string { return $this->countryCode; }
    public function setCountryCode(string $countryCode): self { $this->countryCode = $countryCode; return $this; }

    public function getPopulation(): ?int { return $this->population; }
    public function setPopulation(?int $population): self { $this->population = $population; return $this; }

    public function getLat(): string { return $this->lat; }
    public function setLat(string $lat): self { $this->lat = $lat; return $this; }

    public function getLon(): string { return $this->lon; }
    public function setLon(string $lon): self { $this->lon = $lon; return $this; }
}
