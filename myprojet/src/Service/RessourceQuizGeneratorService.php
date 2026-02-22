<?php

namespace App\Service;

use App\Entity\Ressource;
use App\Entity\RessourceQuiz;
use Doctrine\ORM\EntityManagerInterface;

class RessourceQuizGeneratorService
{
    private ?bool $hasRessourceQuizTable = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function regenerateForRessource(Ressource $ressource): void
    {
        if (!$this->hasRessourceQuizTable()) {
            return;
        }

        foreach ($ressource->getQuizzes() as $quiz) {
            $this->entityManager->remove($quiz);
        }

        $payloads = $this->buildQuizPayloads($ressource);

        $position = 1;
        foreach ($payloads as $payload) {
            $quiz = (new RessourceQuiz())
                ->setRessource($ressource)
                ->setType($payload['type'])
                ->setQuestion($payload['question'])
                ->setChoices($payload['choices'])
                ->setAnswerHint($payload['answerHint'])
                ->setPosition($position);

            $this->entityManager->persist($quiz);
            ++$position;
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<int, array{type: string, question: string, choices: array<int, string>, answer_hint: ?string}>
     */
    public function buildPreviewForRessource(Ressource $ressource): array
    {
        $payloads = $this->buildQuizPayloads($ressource);
        $result = [];

        foreach ($payloads as $payload) {
            $result[] = [
                'type' => (string) $payload['type'],
                'question' => (string) $payload['question'],
                'choices' => array_values(array_filter((array) ($payload['choices'] ?? []), static fn (mixed $item): bool => is_string($item))),
                'answer_hint' => isset($payload['answerHint']) && is_string($payload['answerHint']) ? $payload['answerHint'] : null,
            ];
        }

        return $result;
    }

    private function hasRessourceQuizTable(): bool
    {
        if ($this->hasRessourceQuizTable !== null) {
            return $this->hasRessourceQuizTable;
        }

        try {
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $this->hasRessourceQuizTable = $schemaManager->tablesExist(['ressource_quiz']);
        } catch (\Throwable) {
            $this->hasRessourceQuizTable = false;
        }

        return $this->hasRessourceQuizTable;
    }

    /**
     * @return array<int, array{type: string, question: string, choices: ?array<int, string>, answerHint: ?string}>
     */
    private function buildQuizPayloads(Ressource $ressource): array
    {
        $title = trim((string) $ressource->getTitre());
        $category = strtolower(trim((string) ($ressource->getCategorie()?->getNom() ?? 'ressource')));
        $topic = $title !== '' ? $title : 'cette ressource';

        return [
            [
                'type' => RessourceQuiz::TYPE_MCQ,
                'question' => sprintf('QCM 1: Quel est l objectif principal de "%s" ?', $topic),
                'choices' => [
                    sprintf('Comprendre les notions cles de %s', $topic),
                    sprintf('Ignorer le contenu de %s', $topic),
                    'Memoriser sans pratique',
                    'Supprimer les etapes importantes',
                ],
                'answerHint' => 'La bonne reponse est celle orientee comprehension des notions cles.',
            ],
            [
                'type' => RessourceQuiz::TYPE_MCQ,
                'question' => sprintf('QCM 2: Quel type de support est utilise dans cette ressource (%s) ?', $category),
                'choices' => [
                    ucfirst($category),
                    'Tableau blanc',
                    'Support non defini',
                    'Aucun support',
                ],
                'answerHint' => sprintf('La bonne reponse correspond a la categorie "%s".', $category),
            ],
            [
                'type' => RessourceQuiz::TYPE_MCQ,
                'question' => sprintf('QCM 3: Quelle action aide le plus a retenir "%s" ?', $topic),
                'choices' => [
                    'Revoir puis appliquer avec un exemple concret',
                    'Lire une seule fois rapidement',
                    'Eviter les exercices',
                    'Reporter tout apprentissage',
                ],
                'answerHint' => 'La memorisation s ameliore avec revision et application.',
            ],
            [
                'type' => RessourceQuiz::TYPE_OPEN,
                'question' => sprintf('Question ouverte 1: Resume en 3 lignes ce que tu as compris de "%s".', $topic),
                'choices' => null,
                'answerHint' => 'Attendu: idees principales, vocabulaire du cours, exemple concret.',
            ],
            [
                'type' => RessourceQuiz::TYPE_OPEN,
                'question' => sprintf('Question ouverte 2: Donne un cas pratique ou "%s" peut etre utilise.', $topic),
                'choices' => null,
                'answerHint' => 'Attendu: contexte reel, etapes, resultat attendu.',
            ],
        ];
    }
}
