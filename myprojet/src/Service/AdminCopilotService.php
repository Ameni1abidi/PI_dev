<?php

namespace App\Service;

use App\Repository\UtilisateurRepository;

class AdminCopilotService
{
    public function __construct(private UtilisateurRepository $utilisateurRepository)
    {
    }

    /**
     * @return array{answer: string, links: array<int, array{label:string, route:string}>, data: array<string, mixed>}
     */
    public function answer(string $question): array
    {
        $normalized = $this->normalize($question);

        if ($this->looksLikeNewUsersThisWeek($normalized)) {
            return $this->answerNewUsersThisWeek();
        }

        if ($this->looksLikeUnverifiedUsers($normalized)) {
            return $this->answerUnverifiedUsers();
        }

        if ($this->looksLikeRoleDistribution($normalized)) {
            return $this->answerRoleDistribution();
        }

        return $this->answerDefault();
    }

    private function answerNewUsersThisWeek(): array
    {
        $startOfWeek = (new \DateTimeImmutable('now'))->modify('monday this week')->setTime(0, 0, 0);
        $total = $this->utilisateurRepository->countCreatedSince($startOfWeek);

        $answer = sprintf(
            "Du %s jusqu'a aujourd'hui, %d nouveau(x) compte(s) ont ete crees.",
            $startOfWeek->format('d/m/Y'),
            $total
        );

        return [
            'answer' => $answer,
            'links' => [
                ['label' => 'Voir les utilisateurs', 'route' => 'app_utilisateur_index'],
                ['label' => 'Retour dashboard admin', 'route' => 'app_admin'],
            ],
            'data' => [
                'new_users_this_week' => $total,
                'start_of_week' => $startOfWeek,
            ],
        ];
    }

    private function answerUnverifiedUsers(): array
    {
        $users = $this->utilisateurRepository->findUnverifiedUsers(10);
        $count = count($users);

        if ($count === 0) {
            $answer = "Aucun compte non verifie trouve actuellement.";
        } else {
            $emails = array_map(static fn ($user) => (string) $user->getEmail(), $users);
            $answer = sprintf(
                "%d compte(s) non verifie(s) (top 10): %s",
                $count,
                implode(', ', $emails)
            );
        }

        return [
            'answer' => $answer,
            'links' => [
                ['label' => 'Gerer utilisateurs', 'route' => 'app_utilisateur_index'],
            ],
            'data' => [
                'unverified_users' => $users,
                'unverified_count' => $count,
            ],
        ];
    }

    private function answerRoleDistribution(): array
    {
        $roles = ['ROLE_ADMIN', 'ROLE_PARENT', 'ROLE_ETUDIANT', 'ROLE_PROF'];
        $counts = $this->utilisateurRepository->countByRoles($roles);

        $answer = sprintf(
            'Repartition actuelle: Admin=%d, Parent=%d, Eleve=%d, Enseignant=%d.',
            $counts['ROLE_ADMIN'] ?? 0,
            $counts['ROLE_PARENT'] ?? 0,
            $counts['ROLE_ETUDIANT'] ?? 0,
            $counts['ROLE_PROF'] ?? 0
        );

        return [
            'answer' => $answer,
            'links' => [
                ['label' => 'Dashboard admin', 'route' => 'app_admin'],
                ['label' => 'Liste utilisateurs', 'route' => 'app_utilisateur_index'],
            ],
            'data' => [
                'roles' => $counts,
            ],
        ];
    }

    private function answerDefault(): array
    {
        return [
            'answer' => "Je peux repondre a: 'combien de nouveaux comptes cette semaine', 'liste comptes non verifies', 'repartition des roles'.",
            'links' => [
                ['label' => 'Dashboard admin', 'route' => 'app_admin'],
            ],
            'data' => [],
        ];
    }

    private function looksLikeNewUsersThisWeek(string $normalized): bool
    {
        $hasNew = str_contains($normalized, 'nouveau') || str_contains($normalized, 'inscri');
        $hasUsers = str_contains($normalized, 'compte') || str_contains($normalized, 'utilisateur') || str_contains($normalized, 'user');
        $hasWeek = str_contains($normalized, 'semaine');

        return $hasNew && $hasUsers && $hasWeek;
    }

    private function looksLikeUnverifiedUsers(string $normalized): bool
    {
        $hasUsers = str_contains($normalized, 'compte') || str_contains($normalized, 'utilisateur') || str_contains($normalized, 'user');
        $hasVerify = str_contains($normalized, 'non verifie') || str_contains($normalized, 'non verif') || str_contains($normalized, 'pas verifie');

        return $hasUsers && $hasVerify;
    }

    private function looksLikeRoleDistribution(string $normalized): bool
    {
        $hasRoleKeywords = str_contains($normalized, 'repartition')
            || str_contains($normalized, 'role')
            || str_contains($normalized, 'profil');

        $hasPopulationKeywords = str_contains($normalized, 'combien')
            && (
                str_contains($normalized, 'etudiant')
                || str_contains($normalized, 'enseignant')
                || str_contains($normalized, 'prof')
                || str_contains($normalized, 'parent')
                || str_contains($normalized, 'admin')
                || str_contains($normalized, 'utilisateur')
            );

        return $hasRoleKeywords || $hasPopulationKeywords;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $map = [
            'a' => ['a', 'à', 'â', 'ä'],
            'e' => ['e', 'é', 'è', 'ê', 'ë'],
            'i' => ['i', 'î', 'ï'],
            'o' => ['o', 'ô', 'ö'],
            'u' => ['u', 'ù', 'û', 'ü'],
            'c' => ['c', 'ç'],
        ];

        foreach ($map as $ascii => $chars) {
            $text = str_replace($chars, $ascii, $text);
        }

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }
}