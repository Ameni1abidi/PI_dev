<?php

namespace App\Controller;

use App\Entity\Examen;
use App\Repository\ResultatRepository;
use App\Repository\UtilisateurRepository;
use App\Service\EvaluationNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/evaluations')]
final class EvaluationNotificationController extends AbstractController
{
    public function __construct(
        private EvaluationNotificationService $notificationService
    ) {
    }

    #[Route('/{id}/notify-created', name: 'app_eval_notify_created', methods: ['POST'])]
    public function notifyCreated(Examen $examen, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $guard = $this->guardTeacherAccess();
        if ($guard instanceof JsonResponse) {
            return $guard;
        }

        $phones = $utilisateurRepository->findPhonesByRoles(['ROLE_ETUDIANT', 'ROLE_STUDENT', 'ROLE_PARENT']);

        $result = $this->notificationService->sendEvaluationNotification(
            $phones,
            sprintf('Nouvelle evaluation: %s', (string) $examen->getTitre()),
            sprintf(
                "Une nouvelle evaluation a ete planifiee.\n\nTitre: %s\nType: %s\nDate: %s\nDuree: %d minutes\nCours: %s",
                (string) $examen->getTitre(),
                (string) $examen->getType(),
                $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A',
                (int) ($examen->getDuree() ?? 0),
                $examen->getCours()?->getTitre() ?? 'N/A'
            )
        );

        return $this->json([
            'notification' => 'created',
            'recipients_count' => count($phones),
            'result' => $result,
        ], $result['sent'] ? 200 : 400);
    }

    #[Route('/{id}/notify-reminder', name: 'app_eval_notify_reminder', methods: ['POST'])]
    public function notifyReminder(Examen $examen, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $guard = $this->guardTeacherAccess();
        if ($guard instanceof JsonResponse) {
            return $guard;
        }

        $phones = $utilisateurRepository->findPhonesByRoles(['ROLE_ETUDIANT', 'ROLE_STUDENT', 'ROLE_PARENT']);

        $result = $this->notificationService->sendEvaluationNotification(
            $phones,
            sprintf('Rappel evaluation: %s', (string) $examen->getTitre()),
            sprintf(
                "Rappel: l evaluation approche.\n\nTitre: %s\nDate: %s\nDuree: %d minutes\nCours: %s",
                (string) $examen->getTitre(),
                $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A',
                (int) ($examen->getDuree() ?? 0),
                $examen->getCours()?->getTitre() ?? 'N/A'
            )
        );

        return $this->json([
            'notification' => 'reminder',
            'recipients_count' => count($phones),
            'result' => $result,
        ], $result['sent'] ? 200 : 400);
    }

    #[Route('/{id}/notify-results', name: 'app_eval_notify_results', methods: ['POST'])]
    public function notifyResults(
        Examen $examen,
        ResultatRepository $resultatRepository,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {
        $guard = $this->guardTeacherAccess();
        if ($guard instanceof JsonResponse) {
            return $guard;
        }

        $etudiantPhones = $resultatRepository->findEtudiantPhonesByExamenId((int) $examen->getId());
        $parentPhones = $resultatRepository->findLinkedParentPhonesByExamenId((int) $examen->getId());
        $phones = array_values(array_unique(array_merge($etudiantPhones, $parentPhones)));
        if ($phones === []) {
            $phones = $utilisateurRepository->findPhonesByRoles(['ROLE_ETUDIANT', 'ROLE_STUDENT', 'ROLE_PARENT']);
        }

        $result = $this->notificationService->sendEvaluationNotification(
            $phones,
            sprintf('Notes publiees: %s', (string) $examen->getTitre()),
            sprintf(
                "Les notes de l evaluation sont disponibles.\n\nTitre: %s\nType: %s\nDate: %s",
                (string) $examen->getTitre(),
                (string) $examen->getType(),
                $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A'
            )
        );

        return $this->json([
            'notification' => 'results',
            'recipients_count' => count($phones),
            'result' => $result,
        ], $result['sent'] ? 200 : 400);
    }

    private function guardTeacherAccess(): ?JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->json([
                'message' => 'Utilisateur non authentifie.',
            ], 401);
        }

        $roles = (array) $user->getRoles();
        if (
            !in_array('ROLE_PROF', $roles, true)
            && !in_array('ROLE_ENSEIGNANT', $roles, true)
            && !in_array('ROLE_ADMIN', $roles, true)
        ) {
            return $this->json([
                'message' => 'Acces reserve aux enseignants ou administrateurs.',
            ], 403);
        }

        return null;
    }
}
