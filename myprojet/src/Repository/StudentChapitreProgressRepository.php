<?php

namespace App\Repository;

use App\Entity\Chapitre;
use App\Entity\StudentChapitreProgress;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentChapitreProgress>
 */
class StudentChapitreProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentChapitreProgress::class);
    }

    public function findOneByUtilisateurAndChapitre(Utilisateur $utilisateur, Chapitre $chapitre): ?StudentChapitreProgress
    {
        return $this->findOneBy([
            'utilisateur' => $utilisateur,
            'chapitre' => $chapitre,
        ]);
    }

    /**
     * @return StudentChapitreProgress[]
     */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, StudentChapitreProgress>
     */
    public function findMapByUtilisateur(Utilisateur $utilisateur): array
    {
        $rows = $this->findByUtilisateur($utilisateur);
        $map = [];
        foreach ($rows as $row) {
            $chapitre = $row->getChapitre();
            if ($chapitre !== null && $chapitre->getId() !== null) {
                $map[$chapitre->getId()] = $row;
            }
        }
        return $map;
    }

    public function findOrCreate(Utilisateur $utilisateur, Chapitre $chapitre): StudentChapitreProgress
    {
        $progress = $this->findOneByUtilisateurAndChapitre($utilisateur, $chapitre);
        if ($progress instanceof StudentChapitreProgress) {
            return $progress;
        }

        $progress = new StudentChapitreProgress();
        $progress->setUtilisateur($utilisateur);
        $progress->setChapitre($chapitre);
        $progress->setStartedAt(new \DateTimeImmutable());
        $progress->setLastViewedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($progress);

        return $progress;
    }

    /**
     * @param int[] $coursIds
     * @return array<int, int> [coursId => startedStudentsCount]
     */
    public function countStartedStudentsByCoursIds(array $coursIds): array
    {
        if ($coursIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(ch.cours) AS coursId, COUNT(DISTINCT IDENTITY(p.utilisateur)) AS total')
            ->innerJoin('p.chapitre', 'ch')
            ->andWhere('ch.cours IN (:coursIds)')
            ->setParameter('coursIds', $coursIds)
            ->groupBy('ch.cours')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['coursId']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @param int[] $coursIds
     * @return array<int, int> [coursId => startedStudentsCount]
     */
    public function countStartedStudentsByCoursIdsBetween(
        array $coursIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        if ($coursIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(ch.cours) AS coursId, COUNT(DISTINCT IDENTITY(p.utilisateur)) AS total')
            ->innerJoin('p.chapitre', 'ch')
            ->andWhere('ch.cours IN (:coursIds)')
            ->andWhere('p.startedAt IS NOT NULL')
            ->andWhere('p.startedAt >= :fromDate')
            ->andWhere('p.startedAt < :toDate')
            ->setParameter('coursIds', $coursIds)
            ->setParameter('fromDate', $from)
            ->setParameter('toDate', $to)
            ->groupBy('ch.cours')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['coursId']] = (int) $row['total'];
        }

        return $result;
    }
}
