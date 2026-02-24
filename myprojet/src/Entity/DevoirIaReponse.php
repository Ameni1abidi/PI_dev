<?php

namespace App\Entity;

use App\Repository\DevoirIaReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DevoirIaReponseRepository::class)]
class DevoirIaReponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?DevoirIa $devoir = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'eleve_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull]
    private ?Utilisateur $eleve = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $reponsesJson = '{}';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 20)]
    private string $note = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateSoumission = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDevoir(): ?DevoirIa
    {
        return $this->devoir;
    }

    public function setDevoir(?DevoirIa $devoir): static
    {
        $this->devoir = $devoir;

        return $this;
    }

    public function getEleve(): ?Utilisateur
    {
        return $this->eleve;
    }

    public function setEleve(?Utilisateur $eleve): static
    {
        $this->eleve = $eleve;

        return $this;
    }

    public function getReponsesJson(): string
    {
        return $this->reponsesJson;
    }

    public function setReponsesJson(string $reponsesJson): static
    {
        $this->reponsesJson = $reponsesJson;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReponsesArray(): array
    {
        $decoded = json_decode($this->reponsesJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $reponses
     */
    public function setReponsesArray(array $reponses): static
    {
        $json = json_encode($reponses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->reponsesJson = is_string($json) ? $json : '{}';

        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }
}
