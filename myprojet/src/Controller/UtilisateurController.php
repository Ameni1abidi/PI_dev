<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/approve', name: 'app_utilisateur_approve', methods: ['POST'])]
    public function approve(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('approve'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $utilisateur->setStatus(Utilisateur::STATUS_APPROVED);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur approuve.');
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }

    #[Route('/{id}/reject', name: 'app_utilisateur_reject', methods: ['POST'])]
    public function reject(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('reject'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $utilisateur->setStatus(Utilisateur::STATUS_REJECTED);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur rejete.');
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }

    #[Route('/{id}/block', name: 'app_utilisateur_block', methods: ['POST'])]
    public function block(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('block'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $utilisateur->setIsBlocked(true);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur bloque.');
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }

    #[Route('/{id}/unblock', name: 'app_utilisateur_unblock', methods: ['POST'])]
    public function unblock(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('unblock'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $utilisateur->setIsBlocked(false);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur debloque.');
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }
}
