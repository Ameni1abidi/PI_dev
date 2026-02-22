<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /**
     * @return Ressource[]
     */
    public function findByCategorieNom(string $categorieNom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.categorie', 'c')
            ->andWhere('LOWER(c.nom) = LOWER(:nom)')
            ->setParameter('nom', trim($categorieNom))
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findByChapitreId(int $chapitreId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :id')
            ->setParameter('id', $chapitreId)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findVisibleByChapitreIdForStudent(int $chapitreId, ?\DateTimeImmutable $now = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :id')
            ->setParameter('id', $chapitreId)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        $this->applyAvailabilityVisibilityForStudent($qb, $now);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findTopByChapitreId(int $chapitreId, int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :id')
            ->setParameter('id', $chapitreId)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findTopVisibleByChapitreIdForStudent(int $chapitreId, int $limit = 3, ?\DateTimeImmutable $now = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :id')
            ->setParameter('id', $chapitreId)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit);

        $this->applyAvailabilityVisibilityForStudent($qb, $now);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findAllByScoreDesc(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findCalendarResources(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $chapitreId = null,
        ?string $categorieNom = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')
            ->andWhere('r.availableAt IS NOT NULL')
            ->andWhere('r.availableAt >= :start')
            ->andWhere('r.availableAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.availableAt', 'ASC');

        if ($chapitreId !== null) {
            $qb
                ->innerJoin('r.chapitre', 'ch')
                ->andWhere('ch.id = :chapitreId')
                ->setParameter('chapitreId', $chapitreId);
        }

        if ($categorieNom !== null && $categorieNom !== '') {
            $qb
                ->andWhere('LOWER(c.nom) = LOWER(:categorieNom)')
                ->setParameter('categorieNom', trim($categorieNom));
        }

        return $qb->getQuery()->getResult();
    }

    private function applyAvailabilityVisibilityForStudent(\Doctrine\ORM\QueryBuilder $qb, ?\DateTimeImmutable $now = null): void
    {
        $referenceNow = $now ?? new \DateTimeImmutable();
        $qb
            ->andWhere('r.availableAt IS NULL OR r.availableAt <= :now')
            ->setParameter('now', $referenceNow);
    }

    /**
     * @return array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}>
     */
    public function getMetricsByRessourceForChapitre(int $chapitreId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.titre AS titre')
            ->addSelect('r.nbVues AS vues')
            ->addSelect('r.nbLikes AS likes')
            ->addSelect('r.nbFavoris AS favoris')
            ->addSelect('r.score AS score')
            ->addSelect('COALESCE(c.nom, :uncategorized) AS categorie')
            ->leftJoin('r.categorie', 'c')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :chapitreId')
            ->setParameter('chapitreId', $chapitreId)
            ->setParameter('uncategorized', 'Sans categorie')
            ->orderBy('r.score', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'titre' => (string) $row['titre'],
                'vues' => (int) $row['vues'],
                'likes' => (int) $row['likes'],
                'favoris' => (int) $row['favoris'],
                'score' => (int) $row['score'],
                'categorie' => (string) $row['categorie'],
            ],
            $rows
        );
    }

    /**
     * @return array<int, array{categorie: string, total: int}>
     */
    public function getCategoryDistributionForChapitre(int $chapitreId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('COALESCE(c.nom, :uncategorized) AS categorie')
            ->addSelect('COUNT(r.id) AS total')
            ->leftJoin('r.categorie', 'c')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :chapitreId')
            ->setParameter('chapitreId', $chapitreId)
            ->setParameter('uncategorized', 'Sans categorie')
            ->groupBy('categorie')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'categorie' => (string) $row['categorie'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }

    //    /**
    //     * @return Ressource[] Returns an array of Ressource objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Ressource
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
