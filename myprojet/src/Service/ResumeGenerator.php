<?php

namespace App\Service;

use App\Entity\Chapitre;
use Smalot\PdfParser\Parser;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ResumeGenerator
{
    private $httpClient;
    private $uploadsDirectory;

    public function __construct(
        HttpClientInterface $httpClient, 
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        // On récupère le chemin configuré dans services.yaml
        $this->uploadsDirectory = $params->get('chapitres_directory');
    }

 public function generateAndSave(Chapitre $chapitre): string
{
    set_time_limit(120);

    $texte = '';

    if ($chapitre->getContenuFichier()) {
        $pdfPath = $this->uploadsDirectory . '/' . $chapitre->getContenuFichier();
        $texte = $this->extractTextFromPdf($pdfPath);
    } elseif ($chapitre->getContenuTexte()) {
        $texte = $chapitre->getContenuTexte();
    }

    if (empty($texte)) {
        return "Aucun contenu à résumer.";
    }

    $texte = substr($texte, 0, 2400);

    $prompt = "Résume le texte suivant pour un étudiant : 
• 3 points clés maximum
• Ajouter des emojis pour les titres
• Résumé clair, attractif, facile à lire
• Pas de code, pas de détails techniques
\n\n" . $texte;

    try {
        $response = $this->httpClient->request('POST', 'http://127.0.0.1:11434/api/generate', [
            'json' => [
                'model' => 'phi',
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_predict' => 150
                ]
            ],
            'timeout' => 120,
        ]);

        $data = $response->toArray(false);
        $resume = $data['response'] ?? "Résumé non généré.";

        // 🔥 Sauvegarde quand même le fichier pour archive si tu veux
        $resumeFile = $this->uploadsDirectory . '/resume_' . $chapitre->getId() . '.txt';
        file_put_contents($resumeFile, $resume);

        return $resume; // <-- ici on retourne le texte directement
    } catch (\Exception $e) {
        return "Résumé temporairement indisponible";
    }
}

    private function extractTextFromPdf(string $path): string
{
    if (!file_exists($path)) return '';
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($path);
    $text = $pdf->getText();

    // DEBUG : afficher le texte extrait
    file_put_contents(__DIR__ . '/debug_pdf.txt', $text);

    return $text;
}
}
