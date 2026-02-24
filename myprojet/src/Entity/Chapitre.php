<?php

namespace App\Entity;

use App\Repository\ChapitreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChapitreRepository::class)]
class Chapitre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le titre du chapitre est obligatoire.')]
    #[Assert\Length(
        max: 30,
        maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titre = null;

   #[ORM\Column]
    #[Assert\NotNull(message: 'L’ordre du chapitre est obligatoire.')]
    #[Assert\Positive(message: 'L’ordre doit être un nombre positif.')]
    private ?int $ordre = null;

     #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de contenu est obligatoire.')]
    #[Assert\Choice(
        choices: ['texte', 'fichier', 'video', 'devoir', 'exercice_corrige'],
        message: 'Type de contenu invalide.'
    )]
    private ?string $typeContenu = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\When(
        expression: 'this.getTypeContenu() == "texte"',
        constraints: [
            new Assert\NotBlank(message: 'Le contenu texte est obligatoire.')
        ]
    )]
    private ?string $contenuTexte = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contenuFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\When(
        expression: 'this.getTypeContenu() == "video"',
        constraints: [
            new Assert\NotBlank(message: 'Le lien vidéo est obligatoire.'),
            new Assert\Url(message: 'Le lien vidéo doit être une URL valide.')
        ]
    )]
    private ?string $videoUrl = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La durée estimée est obligatoire.')]
    #[Assert\Positive(message: 'La durée doit être positive.')]
    private ?int $dureeEstimee = null;

   #[ORM\ManyToOne(inversedBy: 'chapitres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le cours est obligatoire.')]
    private ?Cours $cours = null;

    #[ORM\OneToMany(targetEntity: Ressource::class, mappedBy: 'chapitre')]
    private Collection $ressources;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getDureeEstimee(): ?int
    {
        return $this->dureeEstimee;
    }

    public function setDureeEstimee(int $dureeEstimee): static
    {
        $this->dureeEstimee = $dureeEstimee;

        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }
    public function getTypeContenu(): ?string
{
    return $this->typeContenu;
}

public function setTypeContenu(string $typeContenu): static
{
    $this->typeContenu = $typeContenu;
    return $this;
}

public function getContenuTexte(): ?string
{
    return $this->contenuTexte;
}

public function setContenuTexte(?string $contenuTexte): static
{
    $this->contenuTexte = $contenuTexte;
    return $this;
}
public function getContenuFichier(): ?string
{
    return $this->contenuFichier;
}

public function setContenuFichier(?string $contenuFichier): static
{
    $this->contenuFichier = $contenuFichier;
    return $this;
}

public function getVideoUrl(): ?string
{
    return $this->videoUrl;
}

public function setVideoUrl(?string $videoUrl): static
{
    $this->videoUrl = $videoUrl;
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
        $ressource->setChapitre($this);
    }

    return $this;
}

public function removeRessource(Ressource $ressource): static
{
    if ($this->ressources->removeElement($ressource)) {
        if ($ressource->getChapitre() === $this) {
            $ressource->setChapitre(null);
        }
    }

    return $this;
}
}
