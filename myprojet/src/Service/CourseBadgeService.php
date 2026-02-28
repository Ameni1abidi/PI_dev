<?php

namespace App\Service;

use App\Entity\Cours;

final class CourseBadgeService
{
    /**
     * @param Cours[] $cours
     * @param array<int, int> $startedTotalByCours
     * @param array<int, int> $startedLast7ByCours
     * @param array<int, int> $startedPrev7ByCours
     * @return array<int, array<int, array{code:string,label:string}>>
     */
    public function buildBadgesForCourses(
        array $cours,
        array $startedTotalByCours,
        array $startedLast7ByCours,
        array $startedPrev7ByCours,
        ?\DateTimeImmutable $now = null
    ): array {
        $now ??= new \DateTimeImmutable();
        $result = [];

        foreach ($cours as $coursItem) {
            $id = $coursItem->getId();
            if ($id === null) {
                continue;
            }

            $total = $startedTotalByCours[$id] ?? 0;
            $last7 = $startedLast7ByCours[$id] ?? 0;
            $prev7 = $startedPrev7ByCours[$id] ?? 0;

            if ($coursItem->getBadge() === 'a_la_une') {
                $result[$id] = [['code' => 'a_la_une', 'label' => 'A la une']];
                continue;
            }

            if ($this->isTrending($last7, $prev7)) {
                $result[$id] = [['code' => 'en_tendance', 'label' => 'En tendance']];
                continue;
            }

            if ($total >= 1) {
                $result[$id] = [['code' => 'tres_populaire', 'label' => 'Populaire']];
                continue;
            }

            if ($this->isNewCourse($coursItem, $now)) {
                $result[$id] = [['code' => 'nouveau', 'label' => 'Nouveau']];
                continue;
            }

            $result[$id] = [];
        }

        return $result;
    }

    private function isNewCourse(Cours $cours, \DateTimeImmutable $now): bool
    {
        $createdAt = $cours->getDateCreation();
        if (!$createdAt instanceof \DateTimeInterface) {
            return false;
        }

        $created = \DateTimeImmutable::createFromInterface($createdAt);
        $days = (int) $created->diff($now)->format('%a');

        return $days <= 7;
    }

    private function isTrending(int $last7, int $prev7): bool
    {
        if ($last7 < 8) {
            return false;
        }

        if ($prev7 === 0) {
            return $last7 >= 12;
        }

        return $last7 >= (int) ceil($prev7 * 1.30) && ($last7 - $prev7) >= 3;
    }
}
