<?php

namespace App\Entity;

use App\Repository\DevoirIaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DevoirIaRepository::class)]
class DevoirIa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas depasser {{ limit }} caracteres.')]
    private ?string $titre = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['facile', 'moyen', 'difficile'], message: 'Niveau invalide.')]
    private string $niveauDifficulte = 'moyen';

    #[ORM\Column]
    #[Assert\Positive(message: 'La duree doit etre positive.')]
    private int $duree = 30;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $contenuJson = '[]';

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre QCM est invalide.')]
    private int $nbQcm = 4;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre vrai/faux est invalide.')]
    private int $nbVraiFaux = 3;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre de reponses courtes est invalide.')]
    private int $nbReponseCourte = 2;

    #[ORM\Column(length: 20)]
    private string $statut = 'publie';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le cours est obligatoire.')]
    private ?Cours $cours = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L enseignant est obligatoire.')]
    private ?Utilisateur $enseignant = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
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

    public function getNiveauDifficulte(): string
    {
        return $this->niveauDifficulte;
    }

    public function setNiveauDifficulte(string $niveauDifficulte): static
    {
        $this->niveauDifficulte = $niveauDifficulte;

        return $this;
    }

    public function getDuree(): int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeInterface $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getContenuJson(): string
    {
        return $this->contenuJson;
    }

    public function setContenuJson(string $contenuJson): static
    {
        $this->contenuJson = $contenuJson;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContenuArray(): array
    {
        $decoded = json_decode($this->contenuJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $contenu
     */
    public function setContenuArray(array $contenu): static
    {
        $json = json_encode($contenu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->contenuJson = is_string($json) ? $json : '{}';

        return $this;
    }

    public function getNbQcm(): int
    {
        return $this->nbQcm;
    }

    public function setNbQcm(int $nbQcm): static
    {
        $this->nbQcm = $nbQcm;

        return $this;
    }

    public function getNbVraiFaux(): int
    {
        return $this->nbVraiFaux;
    }

    public function setNbVraiFaux(int $nbVraiFaux): static
    {
        $this->nbVraiFaux = $nbVraiFaux;

        return $this;
    }

    public function getNbReponseCourte(): int
    {
        return $this->nbReponseCourte;
    }

    public function setNbReponseCourte(int $nbReponseCourte): static
    {
        $this->nbReponseCourte = $nbReponseCourte;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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

    public function getEnseignant(): ?Utilisateur
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Utilisateur $enseignant): static
    {
        $this->enseignant = $enseignant;

        return $this;
    }
}
