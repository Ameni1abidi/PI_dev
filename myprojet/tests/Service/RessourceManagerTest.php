<?php

namespace App\Tests\Service;

use App\Entity\Categorie;
use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\Ressource;
use App\Service\RessourceManager;
use PHPUnit\Framework\TestCase;

class RessourceManagerTest extends TestCase
{
    private RessourceManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RessourceManager();
    }

    // ==================== Tests de validation ====================

    public function testValidRessource(): void
    {
        $ressource = $this->createRessourceValide();
        
        $this->assertTrue($this->manager->validate($ressource));
    }

    public function testRessourceSansTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $ressource = $this->createRessourceValide();
        $ressource->setTitre('');
        
        $this->manager->validate($ressource);
    }

    public function testRessourceAvecTitreTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 3 caractères');

        $ressource = $this->createRessourceValide();
        $ressource->setTitre('AB');
        
        $this->manager->validate($ressource);
    }

    public function testRessourceAvecTypeInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de ressource doit être');

        $ressource = $this->createRessourceValide();
        $ressource->setType('type_invalide');
        
        $this->manager->validate($ressource);
    }

    public function testRessourceSansCategorie(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La catégorie est obligatoire');

        $ressource = $this->createRessourceValide();
        $ressource->setCategorie(null);
        
        $this->manager->validate($ressource);
    }

    public function testRessourceSansChapitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le chapitre est obligatoire');

        $ressource = $this->createRessourceValide();
        $ressource->setChapitre(null);
        
        $this->manager->validate($ressource);
    }

    public function testRessourceAvecContenuVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu ne peut pas être vide');

        $ressource = $this->createRessourceValide();
        $ressource->setContenu('');
        
        $this->manager->validate($ressource);
    }

    // ==================== Tests de calcul du score ====================

    public function testCalculateScoreAvecValeursNulles(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setNbVues(0);
        $ressource->setNbLikes(0);
        $ressource->setNbFavoris(0);

        $this->assertSame(0, $this->manager->calculateScore($ressource));
    }

    public function testCalculateScoreCorrectement(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setNbVues(10);
        $ressource->setNbLikes(5);
        $ressource->setNbFavoris(3);

        // Score = (5 * 3) + (3 * 2) + 10 = 15 + 6 + 10 = 31
        $this->assertSame(31, $this->manager->calculateScore($ressource));
    }

    public function testCalculateScoreAvecValeursNegatives(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setNbVues(-5);
        $ressource->setNbLikes(-2);
        $ressource->setNbFavoris(-1);

        // Les valeurs négatives sont gérées par max(0, ...) dans l'entité
        $this->assertSame(0, $this->manager->calculateScore($ressource));
    }

    // ==================== Tests de détermination du badge ====================

    public function testValidBadgesConstant(): void
    {
        // Vérifier que la constante des badges valides existe et contient les bonnes valeurs
        $badges = RessourceManager::VALID_BADGES;
        $this->assertIsArray($badges);
        $this->assertContains('Faible', $badges);
        $this->assertContains('Moyen', $badges);
        $this->assertContains('Bon', $badges);
        $this->assertContains('Excellent', $badges);
        $this->assertCount(4, $badges);
    }

    public function testDetermineBadgeFaible(): void
    {
        $this->assertSame('Faible', $this->manager->determineBadge(0));
        $this->assertSame('Faible', $this->manager->determineBadge(10));
        $this->assertSame('Faible', $this->manager->determineBadge(19));
    }

    public function testDetermineBadgeMoyen(): void
    {
        $this->assertSame('Moyen', $this->manager->determineBadge(20));
        $this->assertSame('Moyen', $this->manager->determineBadge(30));
        $this->assertSame('Moyen', $this->manager->determineBadge(49));
    }

    public function testDetermineBadgeBon(): void
    {
        $this->assertSame('Bon', $this->manager->determineBadge(50));
        $this->assertSame('Bon', $this->manager->determineBadge(75));
        $this->assertSame('Bon', $this->manager->determineBadge(99));
    }

    public function testDetermineBadgeExcellent(): void
    {
        $this->assertSame('Excellent', $this->manager->determineBadge(100));
        $this->assertSame('Excellent', $this->manager->determineBadge(150));
        $this->assertSame('Excellent', $this->manager->determineBadge(500));
    }

    // ==================== Tests de mise à jour score et badge ====================

    public function testUpdateScoreAndBadge(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setNbVues(10);
        $ressource->setNbLikes(5);
        $ressource->setNbFavoris(3);

        $this->manager->updateScoreAndBadge($ressource);

        // Score = (5 * 3) + (3 * 2) + 10 = 31
        $this->assertSame(31, $ressource->getScore());
        // 31 >= 20 et < 50 donc Moyen
        $this->assertSame('Moyen', $ressource->getBadge());
    }

    public function testUpdateScoreAndBadgeExcellent(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setNbVues(50);
        $ressource->setNbLikes(20);
        $ressource->setNbFavoris(10);

        $this->manager->updateScoreAndBadge($ressource);

        // Score = (20 * 3) + (10 * 2) + 50 = 60 + 20 + 50 = 130
        $this->assertSame(130, $ressource->getScore());
        // 130 >= 100 donc Excellent
        $this->assertSame('Excellent', $ressource->getBadge());
    }

    // ==================== Tests de publication ====================

    public function testCanBePublishedTrue(): void
    {
        $ressource = $this->createRessourceValide();
        
        $this->assertTrue($this->manager->canBePublished($ressource));
    }

    public function testCanBePublishedFalseSansContenu(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setContenu('');
        
        $this->assertFalse($this->manager->canBePublished($ressource));
    }

    public function testCanBePublishedFalseSansCategorie(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setCategorie(null);
        
        $this->assertFalse($this->manager->canBePublished($ressource));
    }

    public function testCanBePublishedFalseSansChapitre(): void
    {
        $ressource = $this->createRessourceValide();
        $ressource->setChapitre(null);
        
        $this->assertFalse($this->manager->canBePublished($ressource));
    }

    // ==================== Méthode helper ====================

    private function createRessourceValide(): Ressource
    {
        $categorie = new Categorie();
        $categorie->setNom('video');

        $cours = new Cours();
        $cours->setTitre('Cours test');
        $cours->setDescription('Description');
        $cours->setNiveau('Debutant');
        $cours->setDateCreation(new \DateTime());

        $chapitre = new Chapitre();
        $chapitre->setTitre('Chapitre 1');
        $chapitre->setOrdre(1);
        $chapitre->setCours($cours);

        $ressource = new Ressource();
        $ressource->setTitre('Ma ressource');
        $ressource->setType('video');
        $ressource->setContenu('Contenu de la ressource');
        $ressource->setCategorie($categorie);
        $ressource->setChapitre($chapitre);

        return $ressource;
    }
}
