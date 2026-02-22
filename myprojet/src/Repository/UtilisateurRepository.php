<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function countCreatedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Utilisateur[]
     */
    public function findUnverifiedUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('verified', false)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<string> $roles
     *
     * @return array<string, int>
     */
    public function countByRoles(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.role AS role, COUNT(u.id) AS total')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', $roles)
            ->groupBy('u.role')
            ->getQuery()
            ->getArrayResult();

        $result = array_fill_keys($roles, 0);
        foreach ($rows as $row) {
            $role = (string) ($row['role'] ?? '');
            if ($role !== '' && array_key_exists($role, $result)) {
                $result[$role] = (int) ($row['total'] ?? 0);
            }
        }

        return $result;
    }
}