<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Enum\StatutFormation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $module = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'formations')]
    #[ORM\JoinTable(name: 'formation_formateurs')]
    private Collection $formateurs;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    #[ORM\Column]
    private ?int $nbParticipants = 0;

    #[ORM\Column(enumType: StatutFormation::class)]
    private ?StatutFormation $statutFormation = null;


    public function __construct()
    {
        $this->formateurs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(string $module): static
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFormateurs(): Collection
    {
        return $this->formateurs;
    }

    public function addFormateur(User $formateur): static
    {
        if (!$this->formateurs->contains($formateur)) {
            $this->formateurs->add($formateur);
        }
        return $this;
    }

    public function removeFormateur(User $formateur): static
    {
        $this->formateurs->removeElement($formateur);
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getNbParticipants(): ?int
    {
        return $this->nbParticipants;
    }

    public function setNbParticipants(int $nbParticipants): static
    {
        $this->nbParticipants = $nbParticipants;
        return $this;
    }

    public function getStatutFormation(): ?StatutFormation
    {
        return $this->statutFormation;
    }

    public function setStatutFormation(StatutFormation $statutFormation): static
    {
        $this->statutFormation = $statutFormation;
        return $this;
    }


    // Dans App\Entity\Formation
#[ORM\Column(nullable: true)]
private ?bool $isApproved = false;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $createdAt = null;

#[ORM\ManyToOne]
private ?User $createdBy = null;

/**
 * @var Collection<int, User>
 */
#[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'participantsFormation')]
#[ORM\JoinTable(name: 'formation_participants')]
private Collection $participants;



// Ajoutez ces méthodes
public function isApproved(): ?bool
{
    return $this->isApproved;
}

public function setIsApproved(bool $isApproved): static
{
    $this->isApproved = $isApproved;
    return $this;
}

public function getCreatedAt(): ?\DateTimeImmutable
{
    return $this->createdAt;
}

public function setCreatedAt(\DateTimeImmutable $createdAt): static
{
    $this->createdAt = $createdAt;
    return $this;
}

public function getCreatedBy(): ?User
{
    return $this->createdBy;
}

public function setCreatedBy(?User $createdBy): static
{
    $this->createdBy = $createdBy;
    return $this;
}

/**
 * @return Collection<int, User>
 */
public function getParticipants(): Collection
{
    return $this->participants;
}

public function addParticipant(User $participant): static
{
    if (!$this->participants->contains($participant)) {
        $this->participants->add($participant);
    }

    return $this;
}

public function removeParticipant(User $participant): static
{
    $this->participants->removeElement($participant);

    return $this;
}
}