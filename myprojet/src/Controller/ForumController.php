<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Commentaire;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forum')]
final class ForumController extends AbstractController
{
    #[Route('/', name: 'app_forum_index', methods: ['GET','POST'])]
    public function index(
        ForumRepository $forumRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        // === AJOUT COMMENTAIRE ===
        if ($request->isMethod('POST')) {

            $contenu = $request->request->get('contenu');
            $forumId = $request->request->get('forum_id');

            if ($contenu && $forumId) {

                $forum = $forumRepository->find($forumId);

                if ($forum) {
                    $commentaire = new Commentaire();
                    $commentaire->setContenu($contenu);
                    $commentaire->setForum($forum);

                    $entityManager->persist($commentaire);
                    $entityManager->flush();
                }

                return $this->redirectToRoute('app_forum_index');
            }
        }

        return $this->render('forum/index.html.twig', [
            'forums' => $forumRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_forum_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forum);
            $entityManager->flush();

            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_forum_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(Forum $forum, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($forum);
        $entityManager->flush();

        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/commentaire/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
    public function deleteCommentaire(Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($commentaire);
        $entityManager->flush();

        return $this->redirectToRoute('app_forum_index');
    }
    #[Route('/commentaire/{id}/edit', name: 'app_commentaire_edit', methods: ['GET','POST'])]
public function editCommentaire(
    Request $request,
    Commentaire $commentaire,
    EntityManagerInterface $entityManager
): Response {

    if ($request->isMethod('POST')) {

        $contenu = $request->request->get('contenu');

        if ($contenu) {
            $commentaire->setContenu($contenu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_forum_index');
    }

    return $this->render('commentaire/edit.html.twig', [
        'commentaire' => $commentaire
    ]);
}

}
