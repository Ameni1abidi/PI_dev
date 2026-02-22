<?php

namespace App\Repository;

use App\Entity\RessourceInteraction;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RessourceInteraction>
 */
class RessourceInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RessourceInteraction::class);
    }

    /**
     * @return array<int, array{day: string, type: string, total: int}>
     */
    public function aggregateDailyByTypeForChapitre(int $chapitreId, \DateTimeImmutable $from): array
    {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $schemaManager = $conn->createSchemaManager();
            if (!$schemaManager->tablesExist(['ressource_interaction'])) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        $platform = $conn->getDatabasePlatform();
        $dayExpr = $platform instanceof SQLitePlatform
            ? "strftime('%Y-%m-%d', ri.created_at)"
            : 'DATE(ri.created_at)';

        $sql = sprintf(
            'SELECT %s AS day, ri.type AS type, COUNT(ri.id) AS total
             FROM ressource_interaction ri
             INNER JOIN ressource r ON r.id = ri.ressource_id
             WHERE r.chapitre_id = :chapitreId
               AND ri.created_at >= :fromDate
             GROUP BY day, ri.type
             ORDER BY day ASC',
            $dayExpr
        );

        try {
            $rows = $conn->executeQuery($sql, [
                'chapitreId' => $chapitreId,
                'fromDate' => $from->format('Y-m-d H:i:s'),
            ])->fetchAllAssociative();
        } catch (\Throwable) {
            return [];
        }

        return array_map(
            static fn (array $row): array => [
                'day' => (string) $row['day'],
                'type' => (string) $row['type'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}
