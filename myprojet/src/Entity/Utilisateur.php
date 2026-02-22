<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Resultat;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
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
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "L’email est obligatoire")]
    #[Assert\Email(message: "Veuillez saisir une adresse email valide")]
    private ?string $email = null;

    #[ORM\Column(length: 200)]
    private ?string $role = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;
    
    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Resultat::class)]
    private Collection $resultats;

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
        $this->ressourceLikes = new ArrayCollection();
        $this->ressourceFavoris = new ArrayCollection();
        $this->ressourceInteractions = new ArrayCollection();
    }


    /* =========================
       Getters & Setters
       ========================= */

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
        return [$this->role ?? 'ROLE_USER'];
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
        // À utiliser si tu stockes des données sensibles temporaires
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
}
