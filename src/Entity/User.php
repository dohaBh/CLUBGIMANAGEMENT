<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Enum\Niveau;
use App\Enum\Statut;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(enumType: Niveau::class)]
    private Niveau $niveau;

    #[ORM\Column(enumType: Statut::class)]
    private Statut $statut;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

    // --- Si l'utilisateur est responsable ---
    #[ORM\OneToOne(mappedBy: 'responsable', targetEntity: Cellule::class)]
    private ?Cellule $celluleResponsable = null;

    #[ORM\Column]
    private ?bool $isPilotage = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Cellule $cellule = null;

        
    #[ORM\ManyToOne(inversedBy: 'organisateurs')]
    private ?Event $event = null;

    /**
     * @var Collection<int, DemandeAdhesion>
     */
    #[ORM\OneToMany(targetEntity: DemandeAdhesion::class, mappedBy: 'user')]
    private Collection $demandeAdhesions;


    private ?string $plainPassword = null;
    /**
     * @var Collection<int, Formation>
     */
    #[ORM\ManyToMany(targetEntity: Formation::class, mappedBy: 'formateurs')]
    private Collection $formations;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationToken = null;

    /**
     * @var Collection<int, Formation>
     */
    #[ORM\ManyToMany(targetEntity: Formation::class, mappedBy: 'participants')]
    private Collection $participantsFormation;

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function __construct()
    {
        $this->formations = new ArrayCollection();
        $this->demandeAdhesions = new ArrayCollection();
        $this->participantsFormation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }
    public function getNiveau(): Niveau
    {
        return $this->niveau;
    }

    public function setNiveau(Niveau $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTime $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }
    
    public function getStatut(): Statut
    {
        return $this->statut;
    }

    public function setStatut(Statut $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    // --- Si l'utilisateur est responsable ---
    public function getCelluleResponsable(): ?Cellule
    {
        return $this->celluleResponsable;
    }

    public function setCelluleResponsable(?Cellule $celluleResponsable): self
    {
        // On synchronise la relation inverse si nécessaire
        if ($celluleResponsable && $celluleResponsable->getResponsable() !== $this) {
            $celluleResponsable->setResponsable($this);
        }

        $this->celluleResponsable = $celluleResponsable;
        return $this;
    }

    public function isPilotage(): ?bool
    {
        return $this->isPilotage;
    }

    public function setIsPilotage(bool $isPilotage): static
    {
        $this->isPilotage = $isPilotage;

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

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->addFormateur($this);
        }

        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            $formation->removeFormateur($this);
        }

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

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
            $demandeAdhesion->setUser($this);
        }

        return $this;
    }

    public function removeDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if ($this->demandeAdhesions->removeElement($demandeAdhesion)) {
            // set the owning side to null (unless already changed)
            if ($demandeAdhesion->getUser() === $this) {
                $demandeAdhesion->setUser(null);
            }
        }

        return $this;
    }

    
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

     // Méthodes requises par PasswordAuthenticatedUserInterface


    // Méthodes requises par UserInterface

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles sur l'utilisateur, effacez-les ici
        $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     * 
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // Pas nécessaire avec les algorithmes modernes comme bcrypt ou argon
        return null;
    }

    /**
     * @return Collection<int, Formation>
     */
    public function getParticipantsFormation(): Collection
    {
        return $this->participantsFormation;
    }

    public function addParticipantsFormation(Formation $participantsFormation): static
    {
        if (!$this->participantsFormation->contains($participantsFormation)) {
            $this->participantsFormation->add($participantsFormation);
            $participantsFormation->addParticipant($this);
        }

        return $this;
    }

    public function removeParticipantsFormation(Formation $participantsFormation): static
    {
        if ($this->participantsFormation->removeElement($participantsFormation)) {
            $participantsFormation->removeParticipant($this);
        }

        return $this;
    }
}
