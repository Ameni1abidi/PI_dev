<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Form\ChapitreType;
use App\Repository\ChapitreRepository;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chapitre')]
final class ChapitreController extends AbstractController
{
    #[Route('/{coursId<\d+>?}', name: 'app_chapitre_index', methods: ['GET'])]
    public function index(ChapitreRepository $chapitreRepo, CoursRepository $coursRepo, ?int $coursId = null): Response
    {
        if ($coursId) {
            $cours = $coursRepo->find($coursId);
            $chapitres = $chapitreRepo->findBy(['cours' => $cours]);
        } else {
            $cours = null;
            $chapitres = $chapitreRepo->findAll();
        }

        return $this->render('chapitre/index.html.twig', [
            'cours' => $cours,
            'chapitres' => $chapitres,
        ]);
    }

    #[Route('/new', name: 'app_chapitre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $chapitre = new Chapitre();
        $form = $this->createForm(ChapitreType::class, $chapitre, [
            'show_text_field' => false,
            'allow_text_type' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('contenuFichier')->getData();
            if ($fichier) {
                $nomFichier = uniqid() . '.' . $fichier->guessExtension();
                $fichier->move($this->getParameter('chapitres_directory'), $nomFichier);
                $chapitre->setContenuFichier($nomFichier);
            }

            $em->persist($chapitre);
            $em->flush();
            $this->addFlash('success', 'Chapitre ajoute avec succes.');

            return $this->redirectToRoute('app_chapitre_index', [
                'coursId' => $chapitre->getCours()?->getId(),
            ]);
        }

        return $this->render('chapitre/new.html.twig', [
            'form' => $form->createView(),
            'hideWeather' => true,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_chapitre_show', methods: ['GET'])]
    public function show(Chapitre $chapitre): Response
    {
        return $this->render('chapitre/show.html.twig', [
            'chapitre' => $chapitre,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_chapitre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Chapitre $chapitre, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ChapitreType::class, $chapitre, [
            'show_text_field' => false,
            'allow_text_type' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('contenuFichier')->getData();
            if ($fichier) {
                $nomFichier = uniqid() . '.' . $fichier->guessExtension();
                $fichier->move($this->getParameter('chapitres_directory'), $nomFichier);
                $chapitre->setContenuFichier($nomFichier);
            }

            $em->flush();
            $this->addFlash('success', 'Chapitre modifie avec succes.');

            return $this->redirectToRoute('app_chapitre_index', [
                'coursId' => $chapitre->getCours()?->getId(),
            ]);
        }

        return $this->render('chapitre/edit.html.twig', [
            'form' => $form->createView(),
            'chapitre' => $chapitre,
            'hideWeather' => true,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_chapitre_delete', methods: ['POST'])]
    public function delete(Request $request, Chapitre $chapitre, EntityManagerInterface $entityManager): Response
    {
        $coursId = $chapitre->getCours()?->getId();

        if ($this->isCsrfTokenValid('delete' . $chapitre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chapitre);
            $entityManager->flush();
            $this->addFlash('success', 'Chapitre supprime avec succes.');
        } else {
            $this->addFlash('error', 'Suppression impossible: token invalide.');
        }

        return $this->redirectToRoute('app_chapitre_index', [
            'coursId' => $coursId,
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/traduire/{id<\d+>}', name: 'app_chapitre_traduire', methods: ['GET'])]
    public function traduirePdf(Chapitre $chapitre): Response
    {
        if (!$chapitre->getContenuFichier()) {
            throw $this->createNotFoundException('Chapitre ou fichier PDF introuvable !');
        }

        $pdfPath = $this->getParameter('chapitres_directory') . '/' . $chapitre->getContenuFichier();
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $tr = new GoogleTranslate('fr');
        $texteTraduit = $tr->translate($text);

        return $this->render('chapitre/traduction.html.twig', [
            'chapitre' => $chapitre,
            'texteTraduit' => $texteTraduit,
            'hideWeather' => true,
        ]);
    }
}
