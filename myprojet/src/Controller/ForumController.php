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

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_forum_index', methods: ['GET','POST'])]
    #[Route('/{context}/forum', name: 'app_forum_index_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET','POST'])]
    public function index(
        ForumRepository $forumRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?string $context = null
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

                $routes = $this->getForumRoutes($context);
                return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
            }
        }

        $routes = $this->getForumRoutes($context);
        $baseTemplate = $this->getForumBaseTemplate($context);

        return $this->render($baseTemplate ? 'forum/index_shell.html.twig' : 'forum/index.html.twig', [
            'forums' => $forumRepository->findAll(),
            'forum_routes' => $routes,
            'forum_route_params' => $this->getForumRouteParams($context),
            'base_template' => $baseTemplate,
        ]);
    }

    #[Route('/forum/new', name: 'app_forum_new', methods: ['GET','POST'])]
    #[Route('/{context}/forum/new', name: 'app_forum_new_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ?string $context = null): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forum);
            $entityManager->flush();

            $routes = $this->getForumRoutes($context);
            return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
        }

        $routes = $this->getForumRoutes($context);
        $baseTemplate = $this->getForumBaseTemplate($context);

        return $this->render($baseTemplate ? 'forum/new_shell.html.twig' : 'forum/new.html.twig', [
            'form' => $form,
            'forum_routes' => $routes,
            'forum_route_params' => $this->getForumRouteParams($context),
            'base_template' => $baseTemplate,
        ]);
    }

    #[Route('/forum/{id}/edit', name: 'app_forum_edit', methods: ['GET','POST'])]
    #[Route('/{context}/forum/{id}/edit', name: 'app_forum_edit_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET','POST'])]
    public function edit(Request $request, Forum $forum, EntityManagerInterface $entityManager, ?string $context = null): Response
    {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $routes = $this->getForumRoutes($context);
            return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
        }

        $routes = $this->getForumRoutes($context);
        $baseTemplate = $this->getForumBaseTemplate($context);

        return $this->render($baseTemplate ? 'forum/edit_shell.html.twig' : 'forum/edit.html.twig', [
            'form' => $form,
            'forum_routes' => $routes,
            'forum_route_params' => $this->getForumRouteParams($context),
            'base_template' => $baseTemplate,
        ]);
    }

    #[Route('/forum/{id}/delete', name: 'app_forum_delete', methods: ['POST'])]
    #[Route('/{context}/forum/{id}/delete', name: 'app_forum_delete_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['POST'])]
    public function delete(Forum $forum, EntityManagerInterface $entityManager, ?string $context = null): Response
    {
        $entityManager->remove($forum);
        $entityManager->flush();

        $routes = $this->getForumRoutes($context);
        return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
    }

    #[Route('/forum/commentaire/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
    #[Route('/{context}/forum/commentaire/{id}/delete', name: 'app_commentaire_delete_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['POST'])]
    public function deleteCommentaire(Commentaire $commentaire, EntityManagerInterface $entityManager, ?string $context = null): Response
    {
        $entityManager->remove($commentaire);
        $entityManager->flush();

        $routes = $this->getForumRoutes($context);
        return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
    }
    #[Route('/forum/commentaire/{id}/edit', name: 'app_commentaire_edit', methods: ['GET','POST'])]
    #[Route('/{context}/forum/commentaire/{id}/edit', name: 'app_commentaire_edit_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET','POST'])]
public function editCommentaire(
    Request $request,
    Commentaire $commentaire,
    EntityManagerInterface $entityManager,
    ?string $context = null
): Response {

    if ($request->isMethod('POST')) {

        $contenu = $request->request->get('contenu');

        if ($contenu) {
            $commentaire->setContenu($contenu);
            $entityManager->flush();
        }

        $routes = $this->getForumRoutes($context);
        return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
    }

    $routes = $this->getForumRoutes($context);
    $baseTemplate = $this->getForumBaseTemplate($context);

    return $this->render($baseTemplate ? 'commentaire/edit_shell.html.twig' : 'commentaire/edit.html.twig', [
        'commentaire' => $commentaire,
        'forum_routes' => $routes,
        'forum_route_params' => $this->getForumRouteParams($context),
        'base_template' => $baseTemplate,
    ]);
}

    private function getForumRoutes(?string $context): array
    {
        if ($context) {
            return [
                'index' => 'app_forum_index_context',
                'new' => 'app_forum_new_context',
                'edit' => 'app_forum_edit_context',
                'delete' => 'app_forum_delete_context',
                'comment_delete' => 'app_commentaire_delete_context',
                'comment_edit' => 'app_commentaire_edit_context',
            ];
        }

        return [
            'index' => 'app_forum_index',
            'new' => 'app_forum_new',
            'edit' => 'app_forum_edit',
            'delete' => 'app_forum_delete',
            'comment_delete' => 'app_commentaire_delete',
            'comment_edit' => 'app_commentaire_edit',
        ];
    }

    private function getForumRouteParams(?string $context): array
    {
        return $context ? ['context' => $context] : [];
    }

    private function getForumBaseTemplate(?string $context): ?string
    {
        return match ($context) {
            'admin' => 'admin_base.html.twig',
            'parent' => 'parent/base.html.twig',
            'student' => 'student/base.html.twig',
            'enseignant' => 'crud_base.html.twig',
            default => null,
        };
    }

}
