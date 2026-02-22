<?php

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RessourceRepository::class)]
class Ressource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le titre ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cloudinaryPublicId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $cloudinaryResourceType = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $availableAt = null;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La categorie est obligatoire.')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    #[Assert\NotNull(message: 'Le chapitre est obligatoire.')]
    private ?Chapitre $chapitre = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $nbVues = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $nbLikes = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $nbFavoris = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $score = 0;

    #[ORM\Column(length: 20, options: ['default' => 'Moyen'])]
    private string $badge = 'Moyen';

    /**
     * @var Collection<int, RessourceLike>
     */
    #[ORM\OneToMany(mappedBy: 'ressource', targetEntity: RessourceLike::class, orphanRemoval: true)]
    private Collection $likes;

    /**
     * @var Collection<int, RessourceFavori>
     */
    #[ORM\OneToMany(mappedBy: 'ressource', targetEntity: RessourceFavori::class, orphanRemoval: true)]
    private Collection $favoris;

    /**
     * @var Collection<int, RessourceInteraction>
     */
    #[ORM\OneToMany(mappedBy: 'ressource', targetEntity: RessourceInteraction::class, orphanRemoval: true)]
    private Collection $interactions;

    /**
     * @var Collection<int, RessourceQuiz>
     */
    #[ORM\OneToMany(mappedBy: 'ressource', targetEntity: RessourceQuiz::class, orphanRemoval: true)]
    private Collection $quizzes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->interactions = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
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

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getCloudinaryPublicId(): ?string
    {
        return $this->cloudinaryPublicId;
    }

    public function setCloudinaryPublicId(?string $cloudinaryPublicId): static
    {
        $this->cloudinaryPublicId = $cloudinaryPublicId;

        return $this;
    }

    public function getCloudinaryResourceType(): ?string
    {
        return $this->cloudinaryResourceType;
    }

    public function setCloudinaryResourceType(?string $cloudinaryResourceType): static
    {
        $this->cloudinaryResourceType = $cloudinaryResourceType;

        return $this;
    }

    public function getAvailableAt(): ?\DateTimeImmutable
    {
        return $this->availableAt;
    }

    public function setAvailableAt(?\DateTimeImmutable $availableAt): static
    {
        $this->availableAt = $availableAt;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getChapitre(): ?Chapitre
    {
        return $this->chapitre;
    }

    public function setChapitre(?Chapitre $chapitre): static
    {
        $this->chapitre = $chapitre;

        return $this;
    }

    public function getNbVues(): int
    {
        return $this->nbVues;
    }

    public function setNbVues(int $nbVues): static
    {
        $this->nbVues = max(0, $nbVues);

        return $this;
    }

    public function incrementNbVues(): static
    {
        ++$this->nbVues;

        return $this;
    }

    public function getNbLikes(): int
    {
        return $this->nbLikes;
    }

    public function setNbLikes(int $nbLikes): static
    {
        $this->nbLikes = max(0, $nbLikes);

        return $this;
    }

    public function incrementNbLikes(): static
    {
        ++$this->nbLikes;

        return $this;
    }

    public function decrementNbLikes(): static
    {
        $this->nbLikes = max(0, $this->nbLikes - 1);

        return $this;
    }

    public function getNbFavoris(): int
    {
        return $this->nbFavoris;
    }

    public function setNbFavoris(int $nbFavoris): static
    {
        $this->nbFavoris = max(0, $nbFavoris);

        return $this;
    }

    public function incrementNbFavoris(): static
    {
        ++$this->nbFavoris;

        return $this;
    }

    public function decrementNbFavoris(): static
    {
        $this->nbFavoris = max(0, $this->nbFavoris - 1);

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = max(0, $score);

        return $this;
    }

    public function getBadge(): string
    {
        return $this->badge;
    }

    public function setBadge(string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * @return Collection<int, RessourceLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(RessourceLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setRessource($this);
        }

        return $this;
    }

    public function removeLike(RessourceLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getRessource() === $this) {
                $like->setRessource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RessourceFavori>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(RessourceFavori $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setRessource($this);
        }

        return $this;
    }

    public function removeFavori(RessourceFavori $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            if ($favori->getRessource() === $this) {
                $favori->setRessource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RessourceInteraction>
     */
    public function getInteractions(): Collection
    {
        return $this->interactions;
    }

    public function addInteraction(RessourceInteraction $interaction): static
    {
        if (!$this->interactions->contains($interaction)) {
            $this->interactions->add($interaction);
            $interaction->setRessource($this);
        }

        return $this;
    }

    public function removeInteraction(RessourceInteraction $interaction): static
    {
        if ($this->interactions->removeElement($interaction)) {
            if ($interaction->getRessource() === $this) {
                $interaction->setRessource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RessourceQuiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(RessourceQuiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setRessource($this);
        }

        return $this;
    }

    public function removeQuiz(RessourceQuiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            if ($quiz->getRessource() === $this) {
                $quiz->setRessource(null);
            }
        }

        return $this;
    }
}
