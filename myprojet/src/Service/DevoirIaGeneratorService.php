<?php

namespace App\Service;

use App\Entity\Cours;
use App\Entity\DevoirIa;
use App\Entity\Chapitre;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DevoirIaGeneratorService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(DevoirIa $devoir): array
    {
        $payload = $this->generateWithOpenAi($devoir);
        if ($payload !== null) {
            return $payload;
        }

        return $this->generateFallback($devoir);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generateWithOpenAi(DevoirIa $devoir): ?array
    {
        $apiKey = $this->getEnvValue('OPENAI_API_KEY');
        if ($apiKey === '') {
            return null;
        }

        $model = $this->getEnvValue('OPENAI_EVALUATION_MODEL');
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $context = $this->buildCourseContext($devoir->getCours());
        if ($context === '') {
            return null;
        }

        $targetCount = $devoir->getNbQcm() + $devoir->getNbVraiFaux() + $devoir->getNbReponseCourte();
        if ($targetCount <= 0) {
            return null;
        }

        $userPrompt = sprintf(
            "Cours et chapitres:\n%s\n\nGenere un devoir scolaire en francais.\nContraintes: %d QCM, %d Vrai/Faux, %d Reponses courtes.\nDifficulte: %s.\nTitre: %s.\nConsignes enseignant: %s\n\nRetourne uniquement un JSON valide avec cette forme:\n{\"title\":string,\"instructions\":string,\"questions\":[{\"type\":\"qcm\"|\"vrai_faux\"|\"reponse_courte\",\"question\":string,\"options\":string[]|null,\"answer\":string,\"explanation\":string}]}\n",
            $context,
            $devoir->getNbQcm(),
            $devoir->getNbVraiFaux(),
            $devoir->getNbReponseCourte(),
            $devoir->getNiveauDifficulte(),
            (string) $devoir->getTitre(),
            (string) ($devoir->getInstructions() ?? 'Aucune')
        );

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant pedagogique. Reponds uniquement en JSON strict, sans markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 1800,
                    'response_format' => ['type' => 'json_object'],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                return null;
            }

            return $this->normalizeGeneratedPayload($decoded, $devoir);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function generateFallback(DevoirIa $devoir): array
    {
        $context = $this->buildCourseContext($devoir->getCours());
        $sentences = preg_split('/(?<=[.!?])\s+/', strip_tags($context)) ?: [];
        $sentences = array_values(array_filter(array_map(static fn ($line): string => trim((string) $line), $sentences), static fn (string $line): bool => mb_strlen($line) > 30));

        if ($sentences === []) {
            $sentences = [
                'Le cours met l accent sur la comprehension, la pratique et la maitrise des notions principales.',
                'Chaque chapitre apporte des exemples concrets et une progression logique.',
                'Les notions doivent etre expliquees avec des mots simples et precis.',
            ];
        }

        $keywords = $this->extractKeywords($context);
        $questions = [];

        for ($i = 0; $i < $devoir->getNbQcm(); ++$i) {
            $focus = $keywords[$i % count($keywords)] ?? 'la notion principale';
            $hint = $sentences[$i % count($sentences)] ?? 'Le cours demande de relier theorie et pratique.';

            $questions[] = [
                'type' => 'qcm',
                'question' => sprintf('Concernant %s, quelle affirmation correspond le mieux au cours ?', $focus),
                'options' => [
                    'A. ' . $hint,
                    'B. ' . 'Le cours rejette totalement cette notion.',
                    'C. ' . 'Cette notion n apparait pas dans le cours.',
                    'D. ' . 'Le cours interdit toute application pratique.',
                ],
                'answer' => 'A',
                'explanation' => 'La proposition A reprend directement l idee presente dans le contenu du cours.',
            ];
        }

        for ($i = 0; $i < $devoir->getNbVraiFaux(); ++$i) {
            $focus = $keywords[$i % count($keywords)] ?? 'le cours';
            $hint = $sentences[$i % count($sentences)] ?? 'Le cours decrit des elements essentiels.';
            $isTrue = $i % 2 === 0;

            $questions[] = [
                'type' => 'vrai_faux',
                'question' => $isTrue
                    ? sprintf('Vrai ou Faux: %s traite explicitement %s.', $devoir->getCours()?->getTitre() ?? 'Ce cours', $focus)
                    : sprintf('Vrai ou Faux: %s affirme que %s est inutile.', $devoir->getCours()?->getTitre() ?? 'Ce cours', $focus),
                'options' => ['Vrai', 'Faux'],
                'answer' => $isTrue ? 'Vrai' : 'Faux',
                'explanation' => $isTrue ? $hint : 'Le contenu ne dit pas que cette notion est inutile, au contraire il la mobilise.',
            ];
        }

        for ($i = 0; $i < $devoir->getNbReponseCourte(); ++$i) {
            $focus = $keywords[$i % count($keywords)] ?? 'une notion du chapitre';
            $hint = $sentences[$i % count($sentences)] ?? 'Une explication structuree est attendue.';

            $questions[] = [
                'type' => 'reponse_courte',
                'question' => sprintf('Explique en 3 a 5 lignes le role de %s dans ce cours.', $focus),
                'options' => null,
                'answer' => $hint,
                'explanation' => 'La reponse attendue doit citer la notion, son utilite et un exemple simple.',
            ];
        }

        return [
            'title' => $devoir->getTitre(),
            'instructions' => $devoir->getInstructions() ?: 'Lis chaque question attentivement puis reponds de facon claire.',
            'questions' => $questions,
            'source' => 'fallback',
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeGeneratedPayload(array $decoded, DevoirIa $devoir): array
    {
        $rawQuestions = $decoded['questions'] ?? [];
        $questions = [];

        if (is_array($rawQuestions)) {
            foreach ($rawQuestions as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $type = strtolower(trim((string) ($item['type'] ?? '')));
                if (!in_array($type, ['qcm', 'vrai_faux', 'reponse_courte'], true)) {
                    continue;
                }

                $questionText = trim((string) ($item['question'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $options = null;
                if (isset($item['options']) && is_array($item['options'])) {
                    $options = array_values(array_filter(array_map(static fn ($value): string => trim((string) $value), $item['options']), static fn (string $value): bool => $value !== ''));
                }

                $questions[] = [
                    'type' => $type,
                    'question' => $questionText,
                    'options' => $options,
                    'answer' => trim((string) ($item['answer'] ?? '')),
                    'explanation' => trim((string) ($item['explanation'] ?? '')),
                ];
            }
        }

        if ($questions === []) {
            return $this->generateFallback($devoir);
        }

        return [
            'title' => trim((string) ($decoded['title'] ?? $devoir->getTitre() ?? 'Devoir')),
            'instructions' => trim((string) ($decoded['instructions'] ?? $devoir->getInstructions() ?? 'Repondez a chaque question.')),
            'questions' => $questions,
            'source' => 'openai',
        ];
    }

    private function buildCourseContext(?Cours $cours): string
    {
        if ($cours === null) {
            return '';
        }

        $chunks = [];
        $chunks[] = sprintf('Cours: %s', $cours->getTitre() ?? 'Sans titre');
        $chunks[] = sprintf('Description: %s', $cours->getDescription() ?? '');
        $chunks[] = sprintf('Niveau: %s', $cours->getNiveau() ?? '');

        $chapitres = $cours->getChapitres()->toArray();
        usort($chapitres, static fn (Chapitre $a, Chapitre $b): int => ($a->getOrdre() ?? 0) <=> ($b->getOrdre() ?? 0));

        foreach (array_slice($chapitres, 0, 12) as $chapitre) {
            $content = $chapitre->getContenuTexte();
            if ($content === null || trim($content) === '') {
                $content = $chapitre->getVideoUrl() ?: ($chapitre->getContenuFichier() ?: 'Contenu non textuel');
            }

            $chunks[] = sprintf(
                'Chapitre %d - %s (%s): %s',
                $chapitre->getOrdre() ?? 0,
                $chapitre->getTitre() ?? 'Sans titre',
                $chapitre->getTypeContenu() ?? 'contenu',
                mb_substr(trim((string) $content), 0, 450)
            );
        }

        return mb_substr(implode("\n", $chunks), 0, 8500);
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $content): array
    {
        $clean = mb_strtolower(strip_tags($content));
        $clean = preg_replace('/[^a-z0-9\s]/i', ' ', $clean) ?? $clean;
        $words = preg_split('/\s+/', $clean) ?: [];

        $stopWords = [
            'avec', 'dans', 'pour', 'cela', 'cette', 'cours', 'chapitre', 'ainsi', 'vous', 'nous', 'leur', 'etre', 'avoir', 'sans', 'plus', 'moins', 'tres', 'mais', 'donc', 'comme', 'ainsi', 'elle', 'elles', 'ils', 'elles', 'des', 'les', 'une', 'sur', 'par', 'que', 'qui', 'est', 'sont', 'aux', 'ses', 'son', 'nos', 'vos', 'pas', 'non', 'oui', 'cet', 'ces', 'du', 'de', 'la', 'le', 'un',
        ];

        $counter = [];
        foreach ($words as $word) {
            $word = trim((string) $word);
            if (mb_strlen($word) < 4 || in_array($word, $stopWords, true)) {
                continue;
            }

            $counter[$word] = ($counter[$word] ?? 0) + 1;
        }

        arsort($counter);
        $keywords = array_keys(array_slice($counter, 0, 12, true));

        return $keywords !== [] ? $keywords : ['notion', 'concept', 'application'];
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
