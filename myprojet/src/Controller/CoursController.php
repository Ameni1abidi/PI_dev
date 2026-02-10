<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    #[Route('/cours', name: 'app_cours_index')]
    public function index(Request $request, CoursRepository $coursRepo): Response
    {
        // récupération du mot-clé pour la recherche
        $keyword = $request->query->get('search'); // ?search=mot

        if ($keyword) {
            // Méthode findByTitre à créer dans ton CoursRepository
            $cours = $coursRepo->findByTitre($keyword);
        } else {
            $cours = $coursRepo->findAll();
        }

        return $this->render('cours/index.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', methods: ['GET'])]
        public function show(Cours $cours): Response
    {
    return $this->render('cours/show.html.twig', [
        'cours' => $cours,
    ]);
    }

    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/edit.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->request->get('_token'))) {
        $entityManager->remove($cour);
        $entityManager->flush();
        $this->addFlash('success', 'Cours supprimé avec succès !');
    }

    return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
}
}
