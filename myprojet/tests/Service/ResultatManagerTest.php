<?php

namespace App\Tests\Service;

use App\Entity\Resultat;
use App\Service\ResultatManager;
use PHPUnit\Framework\TestCase;

class ResultatManagerTest extends TestCase
{
    /**
     * Test qu'un résultat valide passe la validation
     */
    public function testValidResultat()
    {
        $resultat = new Resultat();
        $resultat->setNote('15.5');
        $resultat->setAppreciation('Très bon travail');

        $manager = new ResultatManager();

        $this->assertTrue($manager->validate($resultat));
    }

    /**
     * Test que la validation échoue si la note est nulle
     */
    public function testResultatWithoutNote()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note est obligatoire');

        $resultat = new Resultat();

        $manager = new ResultatManager();
        $manager->validate($resultat);
    }

    /**
     * Test que la validation échoue si la note est inférieure à 0
     */
    public function testResultatWithNegativeNote()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note doit être comprise entre 0 et 20');

        $resultat = new Resultat();
        $resultat->setNote('-5');

        $manager = new ResultatManager();
        $manager->validate($resultat);
    }

    /**
     * Test que la validation échoue si la note est supérieure à 20
     */
    public function testResultatWithNoteAbove20()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La note doit être comprise entre 0 et 20');

        $resultat = new Resultat();
        $resultat->setNote('25');

        $manager = new ResultatManager();
        $manager->validate($resultat);
    }

    /**
     * Test que la validation échoue si l'appréciation dépasse 1000 caractères
     */
    public function testResultatWithAppreciationTooLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'appréciation ne doit pas dépasser 1000 caractères');

        $resultat = new Resultat();
        $resultat->setNote('15');
        $resultat->setAppreciation(str_repeat('a', 1001));

        $manager = new ResultatManager();
        $manager->validate($resultat);
    }

    /**
     * Test que l'appréciation à exactement 1000 caractères est valide
     */
    public function testResultatWithAppreciationAtMaxLength()
    {
        $resultat = new Resultat();
        $resultat->setNote('15');
        $resultat->setAppreciation(str_repeat('a', 1000));

        $manager = new ResultatManager();

        $this->assertTrue($manager->validate($resultat));
    }

    /**
     * Test getMention - note >= 16
     */
    public function testGetMentionExcellent()
    {
        $manager = new ResultatManager();

        $this->assertEquals('Excellent', $manager->getMention(16));
        $this->assertEquals('Excellent', $manager->getMention(18));
        $this->assertEquals('Excellent', $manager->getMention(20));
    }

    /**
     * Test getMention - note entre 14 et 15.99
     */
    public function testGetMentionVeryGood()
    {
        $manager = new ResultatManager();

        $this->assertEquals('Très Bien', $manager->getMention(14));
        $this->assertEquals('Très Bien', $manager->getMention(15));
        $this->assertEquals('Très Bien', $manager->getMention(15.99));
    }

    /**
     * Test getMention - note entre 12 et 13.99
     */
    public function testGetMentionGood()
    {
        $manager = new ResultatManager();

        $this->assertEquals('Bien', $manager->getMention(12));
        $this->assertEquals('Bien', $manager->getMention(13));
        $this->assertEquals('Bien', $manager->getMention(13.99));
    }

    /**
     * Test getMention - note entre 10 et 11.99
     */
    public function testGetMentionPassable()
    {
        $manager = new ResultatManager();

        $this->assertEquals('Passable', $manager->getMention(10));
        $this->assertEquals('Passable', $manager->getMention(11));
        $this->assertEquals('Passable', $manager->getMention(11.99));
    }

    /**
     * Test getMention - note inférieure à 10
     */
    public function testGetMentionInsuffisant()
    {
        $manager = new ResultatManager();

        $this->assertEquals('Insuffisant', $manager->getMention(0));
        $this->assertEquals('Insuffisant', $manager->getMention(5));
        $this->assertEquals('Insuffisant', $manager->getMention(9.99));
    }

    /**
     * Test hasPassed - note >= 10
     */
    public function testHasPassedReturnsTrue()
    {
        $resultat = new Resultat();
        $resultat->setNote('12');

        $manager = new ResultatManager();

        $this->assertTrue($manager->hasPassed($resultat));
    }

    /**
     * Test hasPassed - note < 10
     */
    public function testHasPassedReturnsFalse()
    {
        $resultat = new Resultat();
        $resultat->setNote('8');

        $manager = new ResultatManager();

        $this->assertFalse($manager->hasPassed($resultat));
    }

    /**
     * Test hasPassed - note exactement 10
     */
    public function testHasPassedAtBoundary()
    {
        $resultat = new Resultat();
        $resultat->setNote('10');

        $manager = new ResultatManager();

        $this->assertTrue($manager->hasPassed($resultat));
    }
}
