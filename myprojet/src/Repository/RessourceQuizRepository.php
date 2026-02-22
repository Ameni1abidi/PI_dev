<?php

namespace App\Repository;

use App\Entity\RessourceQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RessourceQuiz>
 */
class RessourceQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RessourceQuiz::class);
    }

    /**
     * @param int[] $ressourceIds
     *
     * @return array<int, array<int, array{type: string, question: string, choices: array<int, string>, answer_hint: ?string}>>
     */
    public function findGroupedByRessourceIds(array $ressourceIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ressourceIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('q')
            ->select('IDENTITY(q.ressource) AS ressource_id')
            ->addSelect('q.type AS type')
            ->addSelect('q.question AS question')
            ->addSelect('q.choices AS choices')
            ->addSelect('q.answerHint AS answer_hint')
            ->addSelect('q.position AS position')
            ->andWhere('q.ressource IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('q.ressource', 'ASC')
            ->addOrderBy('q.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $grouped = [];
        foreach ($rows as $row) {
            $resourceId = (int) $row['ressource_id'];
            $grouped[$resourceId] ??= [];
            $grouped[$resourceId][] = [
                'type' => (string) $row['type'],
                'question' => (string) $row['question'],
                'choices' => array_values(array_filter((array) ($row['choices'] ?? []), static fn (mixed $item): bool => is_string($item))),
                'answer_hint' => isset($row['answer_hint']) && is_string($row['answer_hint']) ? $row['answer_hint'] : null,
            ];
        }

        return $grouped;
    }
}
