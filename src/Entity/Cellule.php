<?php

namespace App\Entity;

use App\Repository\CelluleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CelluleRepository::class)]
class Cellule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\OneToOne(inversedBy: 'cellule', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $responsable = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $nbMembres = 0;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'cellule')]
    private Collection $users;

    /**
     * @var Collection<int, DemandeAdhesion>
     */
    #[ORM\OneToMany(targetEntity: DemandeAdhesion::class, mappedBy: 'cellule')]
    private Collection $demandeAdhesions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->demandeAdhesions = new ArrayCollection();
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(User $responsable): static
    {
        $this->responsable = $responsable;

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

    public function getNbMembres(): ?int
    {
        return $this->nbMembres;
    }

    public function setNbMembres(int $nbMembres): static
    {
        $this->nbMembres = $nbMembres;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCellule($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCellule() === $this) {
                $user->setCellule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DemandeAdhesion>
     */
    public function getDemandeAdhesions(): Collection
    {
        return $this->demandeAdhesions;
    }

    public function addDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if (!$this->demandeAdhesions->contains($demandeAdhesion)) {
            $this->demandeAdhesions->add($demandeAdhesion);
            $demandeAdhesion->setCellule($this);
        }

        return $this;
    }

    public function removeDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if ($this->demandeAdhesions->removeElement($demandeAdhesion)) {
            // set the owning side to null (unless already changed)
            if ($demandeAdhesion->getCellule() === $this) {
                $demandeAdhesion->setCellule(null);
            }
        }

        return $this;
    }
}
