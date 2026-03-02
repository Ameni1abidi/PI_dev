<?php

namespace App\Service;

use App\Entity\Examen;

class ExamenManager
{
    private const VALID_TYPES = ['quiz', 'devoir', 'examen'];

    /**
     * Valide un examen selon les règles métier:
     * - Le titre est obligatoire
     * - La durée doit être positive
     * - Le type doit être valide (quiz, devoir, examen)
     * 
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(Examen $examen): bool
    {
        // Validation du titre obligatoire
        if (empty($examen->getTitre())) {
            throw new \InvalidArgumentException('Le titre est obligatoire');
        }

        // Validation de la durée positive
        if ($examen->getDuree() === null || $examen->getDuree() <= 0) {
            throw new \InvalidArgumentException('La durée doit être positive');
        }

        // Validation du type valide
        if (!in_array($examen->getType(), self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Le type doit être: quiz, devoir ou examen');
        }

        return true;
    }

    /**
     * Vérifie si un examen peut être passé à une date donnée
     */
    public function canBeTakenAt(Examen $examen, \DateTimeInterface $date): bool
    {
        if ($examen->getDateExamen() === null) {
            return false;
        }

        return $examen->getDateExamen() <= $date;
    }

    /**
     * Calcule le temps restant avant le début de l'examen
     */
    public function getTimeUntilExam(Examen $examen): ?\DateInterval
    {
        if ($examen->getDateExamen() === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        return $now->diff($examen->getDateExamen());
    }
}
