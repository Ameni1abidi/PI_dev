<?php

namespace App\Tests\Controller;

use App\Entity\Categorie;
use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\Ressource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RessourceControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $ressourceRepository;
    private string $path = '/ressource/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->ressourceRepository = $this->manager->getRepository(Ressource::class);

        foreach ($this->ressourceRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Ressource index');
    }

    public function testNewPersistsRessourceWithLienCategorie(): void
    {
        $categorie = $this->createCategorie('lien');
        $chapitre = $this->createChapitre();

        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Enregistrer', [
            'ressource[titre]' => 'Ressource test',
            'ressource[videoUrl]' => '',
            'ressource[audioUrl]' => '',
            'ressource[lienUrl]' => 'https://example.com/ressource',
            'ressource[categorie]' => (string) $categorie->getId(),
            'ressource[chapitre]' => (string) $chapitre->getId(),
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->ressourceRepository->count([]));
    }

    public function testShow(): void
    {
        $fixture = $this->createRessource('Ressource visible');

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Ressource');
        self::assertSelectorTextContains('table tbody tr:nth-child(2) td', 'Ressource visible');
    }

    public function testEdit(): void
    {
        $fixture = $this->createRessource('Titre initial');
        $categorie = $fixture->getCategorie();
        $chapitre = $fixture->getChapitre();
        self::assertInstanceOf(Categorie::class, $categorie);
        self::assertInstanceOf(Chapitre::class, $chapitre);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'ressource[titre]' => 'Titre modifie',
            'ressource[videoUrl]' => '',
            'ressource[audioUrl]' => '',
            'ressource[lienUrl]' => 'https://example.com/modifie',
            'ressource[categorie]' => (string) $categorie->getId(),
            'ressource[chapitre]' => (string) $chapitre->getId(),
        ]);

        self::assertResponseRedirects('/ressource/');

        $updated = $this->ressourceRepository->find($fixture->getId());
        self::assertInstanceOf(Ressource::class, $updated);

        self::assertSame('Titre modifie', $updated->getTitre());
        self::assertSame('lien', $updated->getType());
        self::assertSame('https://example.com/modifie', $updated->getContenu());
    }

    public function testRemoveDeletesRessource(): void
    {
        $fixture = $this->createRessource('A supprimer');

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/ressource/');
        self::assertSame(0, $this->ressourceRepository->count([]));
    }

    private function createRessource(string $titre): Ressource
    {
        $categorie = $this->createCategorie('lien');
        $chapitre = $this->createChapitre();

        $ressource = (new Ressource())
            ->setTitre($titre)
            ->setType('lien')
            ->setContenu('https://example.com/existant')
            ->setCategorie($categorie)
            ->setChapitre($chapitre);

        $this->manager->persist($ressource);
        $this->manager->flush();

        return $ressource;
    }

    private function createCategorie(string $nom): Categorie
    {
        $categorie = (new Categorie())->setNom($nom);
        $this->manager->persist($categorie);
        $this->manager->flush();

        return $categorie;
    }

    private function createChapitre(): Chapitre
    {
        $cours = (new Cours())
            ->setTitre('Cours test')
            ->setDescription('Description du cours test')
            ->setNiveau('Debutant')
            ->setDateCreation(new \DateTime());

        $chapitre = (new Chapitre())
            ->setTitre('Chapitre test')
            ->setOrdre(1)
            ->setTypeContenu('texte')
            ->setContenuTexte('Contenu de chapitre')
            ->setDureeEstimee(30)
            ->setCours($cours);

        $this->manager->persist($cours);
        $this->manager->persist($chapitre);
        $this->manager->flush();

        return $chapitre;
    }
}
