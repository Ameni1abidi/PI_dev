<?php

namespace App\Tests\Service;

use App\Entity\Examen;
use App\Service\ExamenManager;
use PHPUnit\Framework\TestCase;

class ExamenManagerTest extends TestCase
{
    /**
     * Test qu'un examen valide passe la validation
     */
    public function testValidExamen()
    {
        $examen = new Examen();
        $examen->setTitre('Examen de Mathématiques');
        $examen->setDuree(60);
        $examen->setType('examen');
        $examen->setContenu('Contenu de l\'examen');
        $examen->setDateExamen(new \DateTime('+1 day'));

        $manager = new ExamenManager();

        $this->assertTrue($manager->validate($examen));
    }

    /**
     * Test que la validation échoue si le titre est vide
     */
    public function testExamenWithoutTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $examen = new Examen();
        $examen->setTitre('');
        $examen->setDuree(60);
        $examen->setType('examen');

        $manager = new ExamenManager();
        $manager->validate($examen);
    }

    /**
     * Test que la validation échoue si la durée n'est pas positive
     */
    public function testExamenWithNegativeDuree()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée doit être positive');

        $examen = new Examen();
        $examen->setTitre('Examen de Mathématiques');
        $examen->setDuree(-10);
        $examen->setType('examen');

        $manager = new ExamenManager();
        $manager->validate($examen);
    }

    /**
     * Test que la validation échoue si la durée est nulle
     */
    public function testExamenWithZeroDuree()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée doit être positive');

        $examen = new Examen();
        $examen->setTitre('Examen de Mathématiques');
        $examen->setDuree(0);
        $examen->setType('examen');

        $manager = new ExamenManager();
        $manager->validate($examen);
    }

    /**
     * Test que la validation échoue si le type est invalide
     */
    public function testExamenWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type doit être: quiz, devoir ou examen');

        $examen = new Examen();
        $examen->setTitre('Examen de Mathématiques');
        $examen->setDuree(60);
        $examen->setType('type_invalide');

        $manager = new ExamenManager();
        $manager->validate($examen);
    }

    /**
     * Test que le type 'quiz' est valide
     */
    public function testExamenWithQuizType()
    {
        $examen = new Examen();
        $examen->setTitre('Quiz de Culture Générale');
        $examen->setDuree(30);
        $examen->setType('quiz');

        $manager = new ExamenManager();

        $this->assertTrue($manager->validate($examen));
    }

    /**
     * Test que le type 'devoir' est valide
     */
    public function testExamenWithDevoirType()
    {
        $examen = new Examen();
        $examen->setTitre('Devoir Maison');
        $examen->setDuree(120);
        $examen->setType('devoir');

        $manager = new ExamenManager();

        $this->assertTrue($manager->validate($examen));
    }

    /**
     * Test canBeTakenAt - examen peut être passé à la date prévue
     */
    public function testCanBeTakenAtReturnsTrue()
    {
        $examen = new Examen();
        $examen->setDateExamen(new \DateTime('2024-01-15 10:00:00'));

        $manager = new ExamenManager();

        $this->assertTrue($manager->canBeTakenAt($examen, new \DateTime('2024-01-15 10:00:00')));
    }

    /**
     * Test canBeTakenAt - examen ne peut pas être passé avant la date
     */
    public function testCanBeTakenAtReturnsFalse()
    {
        $examen = new Examen();
        $examen->setDateExamen(new \DateTime('2024-01-15 10:00:00'));

        $manager = new ExamenManager();

        $this->assertFalse($manager->canBeTakenAt($examen, new \DateTime('2024-01-14 10:00:00')));
    }

    /**
     * Test canBeTakenAt - sans date d'examen
     */
    public function testCanBeTakenAtWithNullDate()
    {
        $examen = new Examen();

        $manager = new ExamenManager();

        $this->assertFalse($manager->canBeTakenAt($examen, new \DateTime()));
    }

    /**
     * Test getTimeUntilExam - retourne un intervalle
     */
    public function testGetTimeUntilExam()
    {
        $examen = new Examen();
        $examen->setDateExamen(new \DateTime('+2 days'));

        $manager = new ExamenManager();

        $this->assertInstanceOf(\DateInterval::class, $manager->getTimeUntilExam($examen));
    }

    /**
     * Test getTimeUntilExam - sans date retourne null
     */
    public function testGetTimeUntilExamWithNullDate()
    {
        $examen = new Examen();

        $manager = new ExamenManager();

        $this->assertNull($manager->getTimeUntilExam($examen));
    }
}
