<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 200)]
    private ?string $email = null;

    #[ORM\Column(length: 200)]
    private ?string $role = null;

    
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    public function isVerified(): bool
{
    return $this->isVerified;
}

public function setIsVerified(bool $isVerified): self
{
    $this->isVerified = $isVerified;
    return $this;
}

    
    public function getUserIdentifier(): string
    {    return (string) $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
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
     public function getPassword(): string
    {
        return (string) $this->password;
    }


    public function setPassword(string $password): self
    {
    $this->password = $password;
    return $this;
    }
       public function eraseCredentials(): void
    {
        // Si tu stockes des données sensibles temporaires, tu peux les effacer ici
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getRoles(): array
{
    // Retourne le rôle stocké, ou ROLE_USER par défaut
    if ($this->role) {
        return [$this->role];
    }

    return ['ROLE_USER'];
}
public function setRole(string $role): self
{
    $this->role = $role;
    return $this;
}
}



