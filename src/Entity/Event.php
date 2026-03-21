<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Enum\StatutFormation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $theme = null;

    #[ORM\Column]
    private ?bool $enCollab = null;

    #[ORM\Column(length: 255)]
    private ?string $partenaire = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $programme = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'event')]
    private Collection $organisateurs;

    #[ORM\Column]
    private ?int $nbOrganisateurs = null;

    #[ORM\Column(enumType: StatutFormation::class)]
    private StatutFormation $statutEvent;

    public function __construct()
    {
        $this->organisateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function isEnCollab(): ?bool
    {
        return $this->enCollab;
    }

    public function setEnCollab(bool $enCollab): static
    {
        $this->enCollab = $enCollab;

        return $this;
    }

    public function getPartenaire(): ?string
    {
        return $this->partenaire;
    }

    public function setPartenaire(string $partenaire): static
    {
        $this->partenaire = $partenaire;

        return $this;
    }

    public function getProgramme(): ?string
    {
        return $this->programme;
    }

    public function setProgramme(string $programme): static
    {
        $this->programme = $programme;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
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

    /**
     * @return Collection<int, User>
     */
    public function getOrganisateurs(): Collection
    {
        return $this->organisateurs;
    }

    public function addOrganisateur(User $organisateur): static
    {
        if (!$this->organisateurs->contains($organisateur)) {
            $this->organisateurs->add($organisateur);
            $organisateur->setEvent($this);
        }

        return $this;
    }

    public function removeOrganisateur(User $organisateur): static
    {
        if ($this->organisateurs->removeElement($organisateur)) {
            // set the owning side to null (unless already changed)
            if ($organisateur->getEvent() === $this) {
                $organisateur->setEvent(null);
            }
        }

        return $this;
    }

    public function getNbOrganisateurs(): ?int
    {
        return $this->nbOrganisateurs;
    }

    public function setNbOrganisateurs(int $nbOrganisateurs): static
    {
        $this->nbOrganisateurs = $nbOrganisateurs;

        return $this;
    }

    public function getStatutEvent(): StatutFormation
    {
        return $this->statutEvent;
    }

    public function setStatutEvent(StatutFormation $statutevent): self
    {
        $this->statutEvent = $statutevent;
        return $this;
    }
}
