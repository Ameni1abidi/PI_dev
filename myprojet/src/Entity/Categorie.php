<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom doit avoir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: Ressource::class, mappedBy: 'categorie')]
    private Collection $ressources;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRessources(): Collection
    {
        return $this->ressources;
    }

    public function addRessource(Ressource $ressource): static
    {
        if (!$this->ressources->contains($ressource)) {
            $this->ressources->add($ressource);
            $ressource->setCategorie($this);
        }
        return $this;
    }

    public function removeRessource(Ressource $ressource): static
    {
        if ($this->ressources->removeElement($ressource)) {
            if ($ressource->getCategorie() === $this) {
                $ressource->setCategorie(null);
            }
        }
        return $this;
    }
}
