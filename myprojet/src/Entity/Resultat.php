<?php

namespace App\Entity;

use App\Repository\ResultatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Utilisateur;


#[ORM\Entity(repositoryClass: ResultatRepository::class)]
class Resultat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull(message: 'La note est obligatoire.')]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.'
    )]
    private ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'L’appréciation ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $appreciation = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L’élève est obligatoire.')]
    #[Assert\Positive(message: 'L’élève doit être un identifiant positif.')]
    private ?int $eleveId = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L’examen est obligatoire.')]
    private ?Examen $examen = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $etudiant = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getAppreciation(): ?string
    {
        return $this->appreciation;
    }

    public function setAppreciation(?string $appreciation): static
    {
        $this->appreciation = $appreciation;

        return $this;
    }

    public function getEleveId(): ?int
    {
        return $this->eleveId;
    }

    public function setEleveId(int $eleveId): static
    {
        $this->eleveId = $eleveId;

        return $this;
    }

    public function getExamen(): ?Examen
    {
        return $this->examen;
    }

    public function setExamen(?Examen $examen): static
    {
        $this->examen = $examen;

        return $this;
    }
    public function getEtudiant(): ?Utilisateur
    {
    return $this->etudiant;
    }

    public function setEtudiant(?Utilisateur $etudiant): self
{
    $this->etudiant = $etudiant;
    $this->eleveId = $etudiant ? $etudiant->getId() : null;
    return $this;
}

}
