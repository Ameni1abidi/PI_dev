<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le titre du cours est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description du cours est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $niveau = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titreTraduit = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionTraduit = null;

     #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private $badge; // pas de #[ORM\Entity] ici

    /**
     * @var Collection<int, Chapitre>
     */
    #[ORM\OneToMany(targetEntity: Chapitre::class, mappedBy: 'cours', orphanRemoval: true, cascade: ['persist'])]
private Collection $chapitres;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'cours')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $enseignant = null;
    public function __construct()
    {
        $this->chapitres = new ArrayCollection();
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

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTime $dateCreation): static
{
    $this->dateCreation = $dateCreation;
    return $this;
}

public function getTitreTraduit(): ?string
{
    return $this->titreTraduit;
}

public function setTitreTraduit(?string $titreTraduit): self
{
    $this->titreTraduit = $titreTraduit;
    return $this;
}

public function getDescriptionTraduit(): ?string
{
    return $this->descriptionTraduit;
}

    public function setDescriptionTraduit(?string $descriptionTraduit): self
{
    $this->descriptionTraduit = $descriptionTraduit;
    return $this;
}

    /**
     * @return Collection<int, Chapitre>
     */
    public function getChapitres(): Collection
    {
        return $this->chapitres;
    }
    

    public function addChapitre(Chapitre $chapitre): static
    {
        if (!$this->chapitres->contains($chapitre)) {
            $this->chapitres->add($chapitre);
            $chapitre->setCours($this);
        }

        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            // set the owning side to null (unless already changed)
            if ($chapitre->getCours() === $this) {
                $chapitre->setCours(null);
            }
        }

        return $this;
    }

    public function getEnseignant(): ?Utilisateur
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Utilisateur $enseignant): static
    {
        $this->enseignant = $enseignant;
        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    // Setter pour badge
    public function setBadge(?string $badge): self
    {
        // Optionnel : tu peux vérifier que la valeur est valide
        $validBadges = ['nouveau', 'populaire', 'a_la_une'];
        if ($badge !== null && !in_array($badge, $validBadges)) {
            throw new \InvalidArgumentException("Badge non valide.");
        }

        $this->badge = $badge;
        return $this;
    }

    

    

}
