<?php

namespace App\Entity;

use App\Repository\RessourceQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RessourceQuizRepository::class)]
#[ORM\Table(name: 'ressource_quiz', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_ressource_quiz_position', columns: ['ressource_id', 'position'])])]
class RessourceQuiz
{
    public const TYPE_MCQ = 'mcq';
    public const TYPE_OPEN = 'open';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ressource $ressource = null;

    #[ORM\Column(length: 12)]
    #[Assert\Choice(choices: [self::TYPE_MCQ, self::TYPE_OPEN])]
    private string $type = self::TYPE_OPEN;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $question = '';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $choices = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answerHint = null;

    #[ORM\Column]
    private int $position = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    public function setRessource(?Ressource $ressource): static
    {
        $this->ressource = $ressource;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = trim($question);

        return $this;
    }

    public function getChoices(): ?array
    {
        return $this->choices;
    }

    public function setChoices(?array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    public function getAnswerHint(): ?string
    {
        return $this->answerHint;
    }

    public function setAnswerHint(?string $answerHint): static
    {
        $this->answerHint = $answerHint !== null ? trim($answerHint) : null;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = max(1, $position);

        return $this;
    }
}
