<?php

namespace App\Service;

use App\Entity\Chapitre;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QcmGenerator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ParameterBagInterface $params,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateForChapitre(Chapitre $chapitre): array
    {
        $source = $this->extractSourceText($chapitre);
        if ('' === $source) {
            return $this->fallbackQuestions($chapitre);
        }

        $prompt = <<<PROMPT
Genere exactement 3 questions QCM de comprehension du chapitre suivant.
Format de sortie STRICT: JSON uniquement, sans markdown.
Schema:
{
  "questions": [
    {
      "question": "texte",
      "options": {"A":"...", "B":"...", "C":"...", "D":"..."},
      "correct": "A"
    }
  ]
}
Regles:
- Questions claires pour etudiant.
- Une seule bonne reponse par question.
- "correct" doit etre A, B, C ou D.
- Texte en francais.

CHAPITRE:
{$source}
PROMPT;

        try {
            $response = $this->httpClient->request('POST', 'http://127.0.0.1:11434/api/generate', [
                'json' => [
                    'model' => 'phi',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['num_predict' => 500],
                ],
                'timeout' => 90,
            ]);

            $data = $response->toArray(false);
            $raw = (string) ($data['response'] ?? '');
            $payload = $this->extractJson($raw);
            if (null === $payload || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return $this->fallbackQuestions($chapitre);
            }

            $normalized = [];
            foreach ($payload['questions'] as $idx => $q) {
                if (!is_array($q) || !isset($q['question'], $q['options'], $q['correct']) || !is_array($q['options'])) {
                    continue;
                }
                $correct = strtoupper((string) $q['correct']);
                if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                    continue;
                }
                $normalized[] = [
                    'id' => (int) $idx,
                    'question' => (string) $q['question'],
                    'options' => [
                        'A' => (string) ($q['options']['A'] ?? ''),
                        'B' => (string) ($q['options']['B'] ?? ''),
                        'C' => (string) ($q['options']['C'] ?? ''),
                        'D' => (string) ($q['options']['D'] ?? ''),
                    ],
                    'correct' => $correct,
                ];
            }

            return [] !== $normalized ? $normalized : $this->fallbackQuestions($chapitre);
        } catch (\Throwable) {
            return $this->fallbackQuestions($chapitre);
        }
    }

    private function extractSourceText(Chapitre $chapitre): string
    {
        $text = '';
        if ($chapitre->getContenuTexte()) {
            $text = (string) $chapitre->getContenuTexte();
        } elseif ($chapitre->getContenuFichier()) {
            $uploads = (string) $this->params->get('chapitres_directory');
            $pdfPath = $uploads . '/' . $chapitre->getContenuFichier();
            if (is_file($pdfPath)) {
                $parser = new Parser();
                $pdf = $parser->parseFile($pdfPath);
                $text = $pdf->getText();
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_substr($text, 0, 3500);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ('' === $raw) {
            return null;
        }

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if (false === $start || false === $end || $end <= $start) {
            return null;
        }

        $json = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackQuestions(Chapitre $chapitre): array
    {
        $title = (string) $chapitre->getTitre();

        return [
            [
                'id' => 0,
                'question' => sprintf('Quelle est l idee principale du chapitre "%s" ?', $title),
                'options' => [
                    'A' => 'Comprendre les notions cles du chapitre',
                    'B' => 'Memoriser sans analyser',
                    'C' => 'Ignorer les definitions',
                    'D' => 'Passer directement au prochain chapitre',
                ],
                'correct' => 'A',
            ],
            [
                'id' => 1,
                'question' => 'Quelle strategie montre une bonne comprehension ?',
                'options' => [
                    'A' => 'Relier les concepts a un exemple concret',
                    'B' => 'Recopier sans comprendre',
                    'C' => 'Eviter les exercices',
                    'D' => 'Sauter les parties difficiles',
                ],
                'correct' => 'A',
            ],
            [
                'id' => 2,
                'question' => 'Que faire si un point du chapitre reste flou ?',
                'options' => [
                    'A' => 'Relire le passage et tester sa comprehension',
                    'B' => 'Laisser tomber ce point',
                    'C' => 'Memoriser une phrase au hasard',
                    'D' => 'Changer totalement de sujet',
                ],
                'correct' => 'A',
            ],
        ];
    }
}

