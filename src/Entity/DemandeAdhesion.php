<?php

namespace App\Entity;

use App\Repository\DemandeAdhesionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutDmd;

#[ORM\Entity(repositoryClass: DemandeAdhesionRepository::class)]
class DemandeAdhesion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'demandeAdhesions')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'demandeAdhesions')]
    private ?Cellule $cellule = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDemande = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $motivation = null;

    #[ORM\ManyToOne(inversedBy: 'demandeAdhesions')]
    private ?User $traitePar = null;

    #[ORM\Column(enumType: StatutDmd::class)]
    private StatutDmd $statutDmd;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCellule(): ?Cellule
    {
        return $this->cellule;
    }

    public function setCellule(?Cellule $cellule): static
    {
        $this->cellule = $cellule;

        return $this;
    }

    public function getDateDemande(): ?\DateTime
    {
        return $this->dateDemande;
    }

    public function setDateDemande(\DateTime $dateDemande): static
    {
        $this->dateDemande = $dateDemande;

        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): static
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getTraitePar(): ?User
    {
        return $this->traitePar;
    }

    public function setTraitePar(?User $traitePar): static
    {
        $this->traitePar = $traitePar;

        return $this;
    }
        
    public function getStatutDmd(): StatutDmd
    {
        return $this->statutDmd;
    }

    public function setStatutDmd(StatutDmd $statutDmd): self
    {
        $this->statutDmd = $statutDmd;
        return $this;
    }
}
