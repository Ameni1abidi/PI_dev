<?php

namespace App\Entity;

use App\Repository\StudentChapitreProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentChapitreProgressRepository::class)]
#[ORM\Table(name: 'student_chapitre_progress')]
#[ORM\UniqueConstraint(name: 'uniq_progress_user_chapitre', columns: ['utilisateur_id', 'chapitre_id'])]
class StudentChapitreProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Chapitre $chapitre = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastViewedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private int $timeSpentSeconds = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getLastViewedAt(): ?\DateTimeImmutable
    {
        return $this->lastViewedAt;
    }

    public function setLastViewedAt(?\DateTimeImmutable $lastViewedAt): static
    {
        $this->lastViewedAt = $lastViewedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getTimeSpentSeconds(): int
    {
        return $this->timeSpentSeconds;
    }

    public function setTimeSpentSeconds(int $timeSpentSeconds): static
    {
        $this->timeSpentSeconds = max(0, $timeSpentSeconds);
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }
}
