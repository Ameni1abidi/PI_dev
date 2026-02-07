<?php

namespace App\Tests\Controller;

use App\Entity\Chapitre;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ChapitreControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $chapitreRepository;
    private string $path = '/chapitre/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->chapitreRepository = $this->manager->getRepository(Chapitre::class);

        foreach ($this->chapitreRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Chapitre index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'chapitre[titre]' => 'Testing',
            'chapitre[contenu]' => 'Testing',
            'chapitre[ordre]' => 'Testing',
            'chapitre[dureeEstimee]' => 'Testing',
            'chapitre[cours]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->chapitreRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Chapitre();
        $fixture->setTitre('My Title');
        $fixture->setContenu('My Title');
        $fixture->setOrdre('My Title');
        $fixture->setDureeEstimee('My Title');
        $fixture->setCours('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Chapitre');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Chapitre();
        $fixture->setTitre('Value');
        $fixture->setContenu('Value');
        $fixture->setOrdre('Value');
        $fixture->setDureeEstimee('Value');
        $fixture->setCours('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'chapitre[titre]' => 'Something New',
            'chapitre[contenu]' => 'Something New',
            'chapitre[ordre]' => 'Something New',
            'chapitre[dureeEstimee]' => 'Something New',
            'chapitre[cours]' => 'Something New',
        ]);

        self::assertResponseRedirects('/chapitre/');

        $fixture = $this->chapitreRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitre());
        self::assertSame('Something New', $fixture[0]->getContenu());
        self::assertSame('Something New', $fixture[0]->getOrdre());
        self::assertSame('Something New', $fixture[0]->getDureeEstimee());
        self::assertSame('Something New', $fixture[0]->getCours());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Chapitre();
        $fixture->setTitre('Value');
        $fixture->setContenu('Value');
        $fixture->setOrdre('Value');
        $fixture->setDureeEstimee('Value');
        $fixture->setCours('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/chapitre/');
        self::assertSame(0, $this->chapitreRepository->count([]));
    }
}
