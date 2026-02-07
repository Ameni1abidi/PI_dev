<?php

namespace App\Tests\Controller;

use App\Entity\Cours;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CoursControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $courRepository;
    private string $path = '/cours/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->courRepository = $this->manager->getRepository(Cours::class);

        foreach ($this->courRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Cour index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'cour[titre]' => 'Testing',
            'cour[description]' => 'Testing',
            'cour[niveau]' => 'Testing',
            'cour[dateCreation]' => 'Testing',
            'cour[yes]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->courRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cours();
        $fixture->setTitre('My Title');
        $fixture->setDescription('My Title');
        $fixture->setNiveau('My Title');
        $fixture->setDateCreation('My Title');
        $fixture->setYes('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Cour');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cours();
        $fixture->setTitre('Value');
        $fixture->setDescription('Value');
        $fixture->setNiveau('Value');
        $fixture->setDateCreation('Value');
        $fixture->setYes('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'cour[titre]' => 'Something New',
            'cour[description]' => 'Something New',
            'cour[niveau]' => 'Something New',
            'cour[dateCreation]' => 'Something New',
            'cour[yes]' => 'Something New',
        ]);

        self::assertResponseRedirects('/cours/');

        $fixture = $this->courRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitre());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getNiveau());
        self::assertSame('Something New', $fixture[0]->getDateCreation());
        self::assertSame('Something New', $fixture[0]->getYes());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cours();
        $fixture->setTitre('Value');
        $fixture->setDescription('Value');
        $fixture->setNiveau('Value');
        $fixture->setDateCreation('Value');
        $fixture->setYes('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/cours/');
        self::assertSame(0, $this->courRepository->count([]));
    }
}
