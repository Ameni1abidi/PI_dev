<?php

namespace App\Controller;

use App\Entity\Resultat;
use App\Form\ResultatType;
use App\Repository\ResultatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/resultat')]
final class ResultatController extends AbstractController
{
    #[Route('/', name: 'app_resultat_index', methods: ['GET'])]
    public function index(Request $request, ResultatRepository $resultatRepository): Response
    {
        $this->setMode($request, 'edit');

        return $this->render('resultat/index.html.twig', [
            'resultats' => $resultatRepository->findAll(),
        ]);
    }

    #[Route('/readonly', name: 'app_resultat_index_readonly', methods: ['GET'])]
    public function indexReadonly(Request $request, ResultatRepository $resultatRepository): Response
    {
        $this->setMode($request, 'readonly');

        return $this->render('resultat/index_readonly.html.twig', [
            'resultats' => $resultatRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_resultat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyIfReadonly($request);

        $resultat = new Resultat();
        $form = $this->createForm(ResultatType::class, $resultat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($resultat);
            $entityManager->flush();

            return $this->redirectToRoute('app_resultat_index');
        }

        return $this->render('resultat/new.html.twig', [
            'resultat' => $resultat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_resultat_show', methods: ['GET'])]
    public function show(Request $request, Resultat $resultat): Response
    {
        if ($this->isReadonly($request)) {
            return $this->render('resultat/show_readonly.html.twig', [
                'resultat' => $resultat,
            ]);
        }

        return $this->render('resultat/show.html.twig', [
            'resultat' => $resultat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_resultat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Resultat $resultat, EntityManagerInterface $entityManager): Response
    {
        $this->denyIfReadonly($request);

        $form = $this->createForm(ResultatType::class, $resultat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_resultat_index');
        }

        return $this->render('resultat/edit.html.twig', [
            'resultat' => $resultat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_resultat_delete', methods: ['POST'])]
    public function delete(Request $request, Resultat $resultat, EntityManagerInterface $entityManager): Response
    {
        $this->denyIfReadonly($request);

        if ($this->isCsrfTokenValid('delete' . $resultat->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($resultat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_resultat_index');
    }

    private function setMode(Request $request, string $mode): void
    {
        $session = $request->getSession();
        if ($session) {
            $session->set('resultat_mode', $mode);
        }
    }

    private function isReadonly(Request $request): bool
    {
        $session = $request->getSession();
        if (!$session) {
            return false;
        }

        return $session->get('resultat_mode') === 'readonly';
    }

    private function denyIfReadonly(Request $request): void
    {
        if ($this->isReadonly($request)) {
            throw $this->createAccessDeniedException('Lecture seule.');
        }
    }
}
