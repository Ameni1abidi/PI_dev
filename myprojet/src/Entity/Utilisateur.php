<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(
        min: 3,
        minMessage: "Le nom doit contenir au moins {{ limit }} caracteres"
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "Veuillez saisir une adresse email valide")]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 200)]
    private ?string $role = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Resultat::class)]
    private Collection $resultats;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $enfants;

    /**
     * @var Collection<int, RessourceLike>
     */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: RessourceLike::class, orphanRemoval: true)]
    private Collection $ressourceLikes;

    /**
     * @var Collection<int, RessourceFavori>
     */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: RessourceFavori::class, orphanRemoval: true)]
    private Collection $ressourceFavoris;

    /**
     * @var Collection<int, RessourceInteraction>
     */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: RessourceInteraction::class)]
    private Collection $ressourceInteractions;

    public function __construct()
    {
        $this->resultats = new ArrayCollection();
        $this->enfants = new ArrayCollection();
        $this->ressourceLikes = new ArrayCollection();
        $this->ressourceFavoris = new ArrayCollection();
        $this->ressourceInteractions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRoles(): array
    {
        $role = $this->role ?? 'ROLE_USER';
        $roles = [$role];

        // Keep backward compatibility between ROLE_STUDENT and ROLE_ETUDIANT.
        if ($role === 'ROLE_STUDENT') {
            $roles[] = 'ROLE_ETUDIANT';
        }
        if ($role === 'ROLE_ETUDIANT') {
            $roles[] = 'ROLE_STUDENT';
        }

        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getResultats(): Collection
    {
        return $this->resultats;
    }

    public function addResultat(Resultat $resultat): self
    {
        if (!$this->resultats->contains($resultat)) {
            $this->resultats->add($resultat);
            $resultat->setEtudiant($this);
        }

        return $this;
    }

    public function removeResultat(Resultat $resultat): self
    {
        if ($this->resultats->removeElement($resultat)) {
            if ($resultat->getEtudiant() === $this) {
                $resultat->setEtudiant(null);
            }
        }

        return $this;
    }

    public function getRessourceLikes(): Collection
    {
        return $this->ressourceLikes;
    }

    public function addRessourceLike(RessourceLike $ressourceLike): self
    {
        if (!$this->ressourceLikes->contains($ressourceLike)) {
            $this->ressourceLikes->add($ressourceLike);
            $ressourceLike->setUtilisateur($this);
        }

        return $this;
    }

    public function removeRessourceLike(RessourceLike $ressourceLike): self
    {
        if ($this->ressourceLikes->removeElement($ressourceLike)) {
            if ($ressourceLike->getUtilisateur() === $this) {
                $ressourceLike->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getRessourceFavoris(): Collection
    {
        return $this->ressourceFavoris;
    }

    public function addRessourceFavori(RessourceFavori $ressourceFavori): self
    {
        if (!$this->ressourceFavoris->contains($ressourceFavori)) {
            $this->ressourceFavoris->add($ressourceFavori);
            $ressourceFavori->setUtilisateur($this);
        }

        return $this;
    }

    public function removeRessourceFavori(RessourceFavori $ressourceFavori): self
    {
        if ($this->ressourceFavoris->removeElement($ressourceFavori)) {
            if ($ressourceFavori->getUtilisateur() === $this) {
                $ressourceFavori->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getRessourceInteractions(): Collection
    {
        return $this->ressourceInteractions;
    }

    public function addRessourceInteraction(RessourceInteraction $ressourceInteraction): self
    {
        if (!$this->ressourceInteractions->contains($ressourceInteraction)) {
            $this->ressourceInteractions->add($ressourceInteraction);
            $ressourceInteraction->setUtilisateur($this);
        }

        return $this;
    }

    public function removeRessourceInteraction(RessourceInteraction $ressourceInteraction): self
    {
        if ($this->ressourceInteractions->removeElement($ressourceInteraction)) {
            if ($ressourceInteraction->getUtilisateur() === $this) {
                $ressourceInteraction->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(self $enfant): self
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): self
    {
        if ($this->enfants->removeElement($enfant)) {
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
