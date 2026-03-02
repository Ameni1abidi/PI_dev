<?php

namespace App\Tests\Controller;

use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategorieControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $categorieRepository;
    private string $path = '/categorie/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->categorieRepository = $this->manager->getRepository(Categorie::class);

        foreach ($this->categorieRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Categorie index');
    }

    public function testNewPersistsCategorie(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Enregistrer', [
            'categorie[nom]' => 'video',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->categorieRepository->count([]));
    }

    public function testShow(): void
    {
        $fixture = new Categorie();
        $fixture->setNom('audio');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Categorie');
        self::assertSelectorTextContains('table tbody tr:nth-child(2) td', 'audio');
    }

    public function testEdit(): void
    {
        $fixture = new Categorie();
        $fixture->setNom('lien');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'categorie[nom]' => 'pdf',
        ]);

        self::assertResponseRedirects('/categorie/');

        $updated = $this->categorieRepository->find($fixture->getId());

        self::assertInstanceOf(Categorie::class, $updated);
        self::assertSame('pdf', $updated->getNom());
    }

    public function testRemoveDeletesCategorie(): void
    {
        $fixture = new Categorie();
        $fixture->setNom('image');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/categorie/');
        self::assertSame(0, $this->categorieRepository->count([]));
    }
}
