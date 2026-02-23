<?php

namespace App\Repository;

use App\Entity\Resultat;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ArrayParameterType;

/**
 * @extends ServiceEntityRepository<Resultat>
 */
class ResultatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resultat::class);
    }

    /**
     * @param int[] $eleveIds
     * @return Resultat[]
     */
    public function findByEleveIds(array $eleveIds): array
    {
        if ($eleveIds === []) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->andWhere('r.eleveId IN (:ids)')
            ->setParameter('ids', $eleveIds, ArrayParameterType::INTEGER)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAverageNoteForStudentAndCours(Utilisateur $student, int $coursId): ?float
    {
        $avgNote = $this->createQueryBuilder('r')
            ->select('AVG(r.note) AS avgNote')
            ->join('r.examen', 'e')
            ->andWhere('(r.etudiant = :student OR r.eleveId = :studentId)')
            ->andWhere('e.coursId = :coursId')
            ->setParameter('student', $student)
            ->setParameter('studentId', $student->getId())
            ->setParameter('coursId', $coursId)
            ->getQuery()
            ->getSingleScalarResult();

        if (null === $avgNote) {
            return null;
        }

        return round((float) $avgNote, 2);
    }

}
