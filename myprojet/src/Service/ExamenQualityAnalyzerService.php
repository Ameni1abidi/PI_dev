<?php

namespace App\Service;

use App\Entity\Chapitre;
use App\Entity\Examen;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExamenQualityAnalyzerService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(Examen $examen): array
    {
        $payload = $this->analyzeWithOpenAi($examen);
        if ($payload !== null) {
            return $payload;
        }

        return $this->analyzeFallback($examen);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeWithOpenAi(Examen $examen): ?array
    {
        $apiKey = $this->getEnvValue('OPENAI_API_KEY');
        if ($apiKey === '') {
            return null;
        }

        $model = $this->getEnvValue('OPENAI_EVALUATION_MODEL');
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $context = $this->buildExamContext($examen);
        if ($context === '') {
            return null;
        }

        $prompt = sprintf(
            "Analyse la qualite pedagogique de cette evaluation. Reponds en francais.\n\nContexte:\n%s\n\nRetourne uniquement un JSON valide avec ce schema:\n{\"global_score\":int,\"verdict\":string,\"dimensions\":{\"clarity\":{\"score\":int,\"comment\":string},\"difficulty\":{\"score\":int,\"comment\":string},\"coverage\":{\"score\":int,\"comment\":string}},\"risks\":[string],\"recommendations\":[string]}\nContraintes: scores de 0 a 100, recommandations concretes et courtes.",
            $context
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
                            'content' => 'Tu es un expert en evaluation pedagogique. Reponds uniquement en JSON strict.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1200,
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

            return $this->normalizePayload($decoded, 'openai');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeFallback(Examen $examen): array
    {
        $title = trim((string) $examen->getTitre());
        $type = trim((string) $examen->getType());
        $duree = (int) ($examen->getDuree() ?? 0);
        $hasFile = trim((string) ($examen->getContenu() ?? '')) !== '';
        $chapitresCount = $examen->getCours()?->getChapitres()->count() ?? 0;

        $clarity = 55;
        if (mb_strlen($title) >= 8) {
            $clarity += 10;
        }
        if ($hasFile) {
            $clarity += 10;
        }
        $clarity = min(100, $clarity);

        $difficulty = 50;
        if ($duree >= 30 && $duree <= 120) {
            $difficulty += 20;
        } elseif ($duree > 0) {
            $difficulty += 8;
        }
        if (in_array($type, ['quiz', 'devoir', 'examen'], true)) {
            $difficulty += 10;
        }
        $difficulty = min(100, $difficulty);

        $coverage = 45;
        if ($chapitresCount >= 3) {
            $coverage += 25;
        } elseif ($chapitresCount > 0) {
            $coverage += 12;
        }
        if ($examen->getCours() !== null) {
            $coverage += 12;
        }
        $coverage = min(100, $coverage);

        $global = (int) round(($clarity + $difficulty + $coverage) / 3);

        $risks = [];
        if (!$hasFile) {
            $risks[] = 'Contenu detaille de l epreuve non detecte.';
        }
        if ($duree < 30) {
            $risks[] = 'Duree potentiellement courte pour evaluer toutes les competences.';
        }
        if ($chapitresCount < 2) {
            $risks[] = 'Couverture de chapitre possiblement limitee.';
        }
        if ($risks === []) {
            $risks[] = 'Aucun risque majeur detecte par l analyse locale.';
        }

        $recommendations = [
            'Ajouter un bareme par section et les criteres de notation.',
            'Verifier que chaque chapitre cle apparait dans les questions.',
            'Ajouter au moins une question d application pratique.',
        ];

        return [
            'global_score' => $global,
            'verdict' => $global >= 75 ? 'Qualite bonne' : ($global >= 60 ? 'Qualite moyenne' : 'Qualite a renforcer'),
            'dimensions' => [
                'clarity' => [
                    'score' => $clarity,
                    'comment' => 'Evaluation de la clarte des consignes et de la formulation generale.',
                ],
                'difficulty' => [
                    'score' => $difficulty,
                    'comment' => 'Adequation presumee entre duree, type d epreuve et niveau attendu.',
                ],
                'coverage' => [
                    'score' => $coverage,
                    'comment' => 'Estimation de la couverture des chapitres et objectifs du cours.',
                ],
            ],
            'risks' => $risks,
            'recommendations' => $recommendations,
            'source' => 'fallback',
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizePayload(array $decoded, string $source): array
    {
        $dimensions = is_array($decoded['dimensions'] ?? null) ? $decoded['dimensions'] : [];
        $clarity = is_array($dimensions['clarity'] ?? null) ? $dimensions['clarity'] : [];
        $difficulty = is_array($dimensions['difficulty'] ?? null) ? $dimensions['difficulty'] : [];
        $coverage = is_array($dimensions['coverage'] ?? null) ? $dimensions['coverage'] : [];

        $normalizeScore = static function (mixed $value): int {
            $score = is_numeric($value) ? (int) round((float) $value) : 0;
            if ($score < 0) {
                return 0;
            }
            if ($score > 100) {
                return 100;
            }

            return $score;
        };

        $result = [
            'global_score' => $normalizeScore($decoded['global_score'] ?? 0),
            'verdict' => trim((string) ($decoded['verdict'] ?? '')),
            'dimensions' => [
                'clarity' => [
                    'score' => $normalizeScore($clarity['score'] ?? 0),
                    'comment' => trim((string) ($clarity['comment'] ?? '')),
                ],
                'difficulty' => [
                    'score' => $normalizeScore($difficulty['score'] ?? 0),
                    'comment' => trim((string) ($difficulty['comment'] ?? '')),
                ],
                'coverage' => [
                    'score' => $normalizeScore($coverage['score'] ?? 0),
                    'comment' => trim((string) ($coverage['comment'] ?? '')),
                ],
            ],
            'risks' => [],
            'recommendations' => [],
            'source' => $source,
        ];

        $risks = $decoded['risks'] ?? [];
        if (is_array($risks)) {
            foreach ($risks as $risk) {
                $text = trim((string) $risk);
                if ($text !== '') {
                    $result['risks'][] = $text;
                }
            }
        }

        $recommendations = $decoded['recommendations'] ?? [];
        if (is_array($recommendations)) {
            foreach ($recommendations as $item) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $result['recommendations'][] = $text;
                }
            }
        }

        if ($result['verdict'] === '') {
            $score = $result['global_score'];
            $result['verdict'] = $score >= 75 ? 'Qualite bonne' : ($score >= 60 ? 'Qualite moyenne' : 'Qualite a renforcer');
        }

        if ($result['risks'] === []) {
            $result['risks'][] = 'Aucun risque explicite remonte par le modele.';
        }

        if ($result['recommendations'] === []) {
            $result['recommendations'][] = 'Ajouter des recommandations detaillees pour ameliorer la qualite de l epreuve.';
        }

        return $result;
    }

    private function buildExamContext(Examen $examen): string
    {
        $chunks = [];
        $chunks[] = sprintf('Titre evaluation: %s', (string) ($examen->getTitre() ?? 'N/A'));
        $chunks[] = sprintf('Type: %s', (string) ($examen->getType() ?? 'N/A'));
        $chunks[] = sprintf('Date: %s', $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A');
        $chunks[] = sprintf('Duree minutes: %d', (int) ($examen->getDuree() ?? 0));
        $chunks[] = sprintf('Fichier contenu: %s', (string) ($examen->getContenu() ?? 'N/A'));

        $cours = $examen->getCours();
        if ($cours !== null) {
            $chunks[] = sprintf('Cours: %s', (string) ($cours->getTitre() ?? 'N/A'));
            $chunks[] = sprintf('Niveau: %s', (string) ($cours->getNiveau() ?? 'N/A'));
            $chunks[] = sprintf('Description cours: %s', mb_substr((string) ($cours->getDescription() ?? ''), 0, 600));

            $chapitres = $cours->getChapitres()->toArray();
            usort($chapitres, static fn (Chapitre $a, Chapitre $b): int => ($a->getOrdre() ?? 0) <=> ($b->getOrdre() ?? 0));

            foreach (array_slice($chapitres, 0, 10) as $chapitre) {
                $content = $chapitre->getContenuTexte();
                if ($content === null || trim($content) === '') {
                    $content = $chapitre->getVideoUrl() ?: ($chapitre->getContenuFichier() ?: 'Contenu non textuel');
                }

                $chunks[] = sprintf(
                    'Chapitre %d - %s: %s',
                    (int) ($chapitre->getOrdre() ?? 0),
                    (string) ($chapitre->getTitre() ?? 'Sans titre'),
                    mb_substr(trim((string) $content), 0, 300)
                );
            }
        }

        return mb_substr(implode("\n", $chunks), 0, 7000);
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}

