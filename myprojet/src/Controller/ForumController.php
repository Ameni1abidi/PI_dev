<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Forum;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use App\Service\OllamaService;
use App\Service\ProfanityFilterService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_forum_index', methods: ['GET', 'POST'])]
    #[Route('/{context}/forum', name: 'app_forum_index_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET', 'POST'])]
    public function index(
        ForumRepository $forumRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ProfanityFilterService $profanityFilterService,
        OllamaService $ollamaService,
        ?string $context = null
    ): Response {
        if ($request->isMethod('POST')) {
            $contenu = trim((string) $request->request->get('contenu', ''));
            $forumId = $request->request->get('forum_id');
            $badWords = $profanityFilterService->findInappropriateWords($contenu);

            if ($forumId) {
                $forum = $forumRepository->find($forumId);

                if ($forum) {
                    if ($badWords !== []) {
                        $this->addFlash(
                            'error',
                            'Commentaire refuse: mots inappropries detectes (' . implode(', ', $badWords) . ').'
                        );

                        $routes = $this->getForumRoutes($context);
                        return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
                    }

                    $commentaire = new Commentaire();
                    $commentaire->setContenu($contenu);
                    $commentaire->setForum($forum);

                    $violations = $validator->validate($commentaire);
                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $this->addFlash('error', $violation->getMessage());
                        }
                    } else {
                        $entityManager->persist($commentaire);
                        $entityManager->flush();
                        $this->addFlash('success', 'Commentaire ajoute avec succes.');
                    }
                } else {
                    $this->addFlash('error', 'Forum introuvable.');
                }

                $routes = $this->getForumRoutes($context);
                return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
            }

            if ($request->request->has('contenu')) {
                $this->addFlash('error', 'Le commentaire ne peut pas etre vide.');
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

    #[Route('/forum/new', name: 'app_forum_new', methods: ['GET', 'POST'])]
    #[Route('/{context}/forum/new', name: 'app_forum_new_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        OllamaService $ollamaService,
        ?string $context = null
    ): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forum);
            $entityManager->flush();

            $prompt = sprintf(
                "Nouveau sujet forum\nTitre: %s\nType: %s\nContenu: %s",
                (string) $forum->getTitre(),
                (string) $forum->getType(),
                (string) $forum->getContenu()
            );

            $assistantReply = $ollamaService->ask($prompt);
            if ($assistantReply !== null) {
                $botCommentaire = new Commentaire();
                $botCommentaire->setContenu($assistantReply);
                $botCommentaire->setForum($forum);

                $botViolations = $validator->validate($botCommentaire);
                if (count($botViolations) === 0) {
                    $entityManager->persist($botCommentaire);
                    $entityManager->flush();
                }
            }

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

    #[Route('/forum/{id}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    #[Route('/{context}/forum/{id}/edit', name: 'app_forum_edit_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET', 'POST'])]
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
        if ($this->isBotComment($commentaire)) {
            $this->addFlash('error', 'Ce commentaire IA ne peut pas etre supprime.');
            $routes = $this->getForumRoutes($context);
            return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();

        $routes = $this->getForumRoutes($context);
        return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
    }

    #[Route('/forum/commentaire/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
    #[Route('/{context}/forum/commentaire/{id}/edit', name: 'app_commentaire_edit_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['GET', 'POST'])]
    public function editCommentaire(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ProfanityFilterService $profanityFilterService,
        ?string $context = null
    ): Response {
        if ($this->isBotComment($commentaire)) {
            $this->addFlash('error', 'Ce commentaire IA ne peut pas etre modifie.');
            $routes = $this->getForumRoutes($context);
            return $this->redirectToRoute($routes['index'], $this->getForumRouteParams($context));
        }

        if ($request->isMethod('POST')) {
            $contenu = trim((string) $request->request->get('contenu', ''));
            $badWords = $profanityFilterService->findInappropriateWords($contenu);

            if ($badWords !== []) {
                $errorMessages = [
                    'Modification refusee: mots inappropries detectes (' . implode(', ', $badWords) . ').',
                ];

                $routes = $this->getForumRoutes($context);
                $baseTemplate = $this->getForumBaseTemplate($context);

                return $this->render($baseTemplate ? 'commentaire/edit_shell.html.twig' : 'commentaire/edit.html.twig', [
                    'commentaire' => $commentaire,
                    'errors' => $errorMessages,
                    'forum_routes' => $routes,
                    'forum_route_params' => $this->getForumRouteParams($context),
                    'base_template' => $baseTemplate,
                ]);
            }

            $commentaire->setContenu($contenu);

            $violations = $validator->validate($commentaire);
            if (count($violations) > 0) {
                $errorMessages = [];
                foreach ($violations as $violation) {
                    $errorMessages[] = $violation->getMessage();
                }

                $routes = $this->getForumRoutes($context);
                $baseTemplate = $this->getForumBaseTemplate($context);

                return $this->render($baseTemplate ? 'commentaire/edit_shell.html.twig' : 'commentaire/edit.html.twig', [
                    'commentaire' => $commentaire,
                    'errors' => $errorMessages,
                    'forum_routes' => $routes,
                    'forum_route_params' => $this->getForumRouteParams($context),
                    'base_template' => $baseTemplate,
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Commentaire modifie avec succes.');

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

    #[Route('/forum/commentaire/{id}/translate', name: 'app_commentaire_translate', methods: ['POST'])]
    #[Route('/{context}/forum/commentaire/{id}/translate', name: 'app_commentaire_translate_context', requirements: ['context' => 'admin|parent|student|enseignant'], methods: ['POST'])]
    public function translateCommentaire(
        Request $request,
        Commentaire $commentaire,
        TranslationService $translationService,
        ?string $context = null
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $target = strtolower(trim((string) ($payload['target'] ?? '')));

        if (!$this->isAllowedTranslationLanguage($target)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Langue non supportee.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $translatedText = $translationService->translate((string) $commentaire->getContenu(), $target);

        return new JsonResponse([
            'success' => true,
            'target' => $target,
            'translatedText' => $translatedText,
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
                'comment_translate' => 'app_commentaire_translate_context',
            ];
        }

        return [
            'index' => 'app_forum_index',
            'new' => 'app_forum_new',
            'edit' => 'app_forum_edit',
            'delete' => 'app_forum_delete',
            'comment_delete' => 'app_commentaire_delete',
            'comment_edit' => 'app_commentaire_edit',
            'comment_translate' => 'app_commentaire_translate',
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

    private function isAllowedTranslationLanguage(string $language): bool
    {
        return in_array($language, ['fr', 'en', 'es', 'de', 'it', 'ar'], true);
    }

    private function isBotComment(Commentaire $commentaire): bool
    {
        $content = (string) $commentaire->getContenu();
        return str_starts_with($content, '[Bot IA] ');
    }

}
