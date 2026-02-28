<?php

namespace App\Service;

use App\Entity\Chapitre;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChapitreChatService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ParameterBagInterface $params
    ) {
    }

    public function ask(Chapitre $chapitre, string $question): string
    {
        $sourceText = $this->getChapitreSourceText($chapitre);
        if ($sourceText === '') {
            return 'Aucun contenu disponible pour repondre a cette question.';
        }

        $selectedContext = $this->selectRelevantContext($sourceText, $question, $chapitre);

        $prompt = "Tu es un assistant pedagogique.\n"
            . "Tu dois repondre UNIQUEMENT avec les informations du contexte fourni.\n"
            . "Si la reponse n'est pas dans le contexte, reponds exactement: 'Je ne trouve pas cette information dans ce chapitre.'\n"
            . "Reponse en francais clair, concise, sans inventer.\n"
            . "Format attendu:\n"
            . "1) Reponse\n"
            . "2) Preuve: cite 1 ou 2 extraits en indiquant [Extrait X].\n\n"
            . "Contexte chapitre:\n" . $selectedContext . "\n\n"
            . "Question etudiant:\n" . trim($question);

        try {
            $response = $this->httpClient->request('POST', 'http://127.0.0.1:11434/api/generate', [
                'json' => [
                    'model' => 'phi',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'num_predict' => 220,
                    ],
                ],
                'timeout' => 120,
            ]);

            $data = $response->toArray(false);
            $answer = trim((string) ($data['response'] ?? ''));

            return $answer !== '' ? $answer : 'Reponse indisponible pour le moment.';
        } catch (\Throwable) {
            return 'Service IA indisponible pour le moment.';
        }
    }

    private function getChapitreSourceText(Chapitre $chapitre): string
    {
        $text = '';
        if ($chapitre->getContenuTexte()) {
            $text = (string) $chapitre->getContenuTexte();
        } elseif ($chapitre->getContenuFichier()) {
            $uploadsDirectory = (string) $this->params->get('chapitres_directory');
            $pdfPath = $uploadsDirectory . '/' . $chapitre->getContenuFichier();
            $text = $this->extractTextFromPdf($pdfPath);
        }

        return trim($text);
    }

    private function extractTextFromPdf(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            return (string) $pdf->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function selectRelevantContext(string $sourceText, string $question, Chapitre $chapitre): string
    {
        $chunks = $this->buildChunks($sourceText, 900, 160);
        $keywords = $this->extractKeywords($question);
        $questionNorm = $this->normalizeText($question);
        $titleNorm = $this->normalizeText((string) $chapitre->getTitre());
        $scored = [];

        foreach ($chunks as $idx => $chunk) {
            $norm = $this->normalizeText($chunk);
            $score = 0.0;

            foreach ($keywords as $keyword) {
                $count = substr_count($norm, $keyword);
                if ($count > 0) {
                    $score += 2.0 + min(2.0, $count * 0.4);
                }
            }

            if ($titleNorm !== '' && str_contains($norm, $titleNorm)) {
                $score += 1.2;
            }

            if ($questionNorm !== '' && str_contains($norm, $questionNorm)) {
                $score += 3.0;
            }

            if ($score > 0.0) {
                $scored[] = ['score' => $score, 'text' => $chunk, 'idx' => $idx + 1];
            }
        }

        usort($scored, static fn (array $a, array $b) => $b['score'] <=> $a['score']);
        $selected = [];
        $length = 0;

        foreach ($scored as $row) {
            if ($length > 5200) {
                break;
            }
            $selected[] = '[Extrait ' . $row['idx'] . "]\n" . $row['text'];
            $length += mb_strlen($row['text']);
            if (count($selected) >= 5) {
                break;
            }
        }

        if ($selected === []) {
            return '[Extrait 1]' . "\n" . mb_substr($sourceText, 0, 5200);
        }

        return implode("\n\n", $selected);
    }

    /**
     * @return string[]
     */
    private function buildChunks(string $text, int $chunkSize, int $overlap): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        $len = mb_strlen($text);
        if ($len <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $step = max(120, $chunkSize - $overlap);
        for ($start = 0; $start < $len; $start += $step) {
            $piece = trim(mb_substr($text, $start, $chunkSize));
            if ($piece !== '' && mb_strlen($piece) >= 80) {
                $chunks[] = $piece;
            }
        }

        return $chunks;
    }

    /**
     * @return string[]
     */
    private function extractKeywords(string $question): array
    {
        $q = $this->normalizeText($question);
        $parts = preg_split('/[^a-z0-9]+/i', $q) ?: [];
        $stop = [
            'le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'ou', 'en', 'dans',
            'sur', 'pour', 'avec', 'par', 'que', 'qui', 'quoi', 'comment', 'est', 'ce',
            'cette', 'ces', 'au', 'aux', 'a', 'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous',
        ];

        $keywords = [];
        foreach ($parts as $part) {
            $w = trim($part);
            if ($w === '' || mb_strlen($w) < 3 || in_array($w, $stop, true)) {
                continue;
            }
            $keywords[] = $w;
        }

        $keywords = array_values(array_unique($keywords));
        $bigrams = [];
        for ($i = 0; $i < count($keywords) - 1; ++$i) {
            $bigrams[] = $keywords[$i] . ' ' . $keywords[$i + 1];
        }

        return array_values(array_unique(array_merge($keywords, $bigrams)));
    }

    private function normalizeText(string $text): string
    {
        $lower = mb_strtolower($text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
        if ($converted === false) {
            $converted = $lower;
        }

        return preg_replace('/\s+/u', ' ', trim($converted)) ?? '';
    }
}
