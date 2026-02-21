<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TranslationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'auto'): string
    {
        if (trim($text) === '' || trim($targetLanguage) === '') {
            return $text;
        }

        $translated = $this->translateWithLibreTranslate($text, $targetLanguage, $sourceLanguage);
        if ($translated !== null) {
            return $translated;
        }

        $translated = $this->translateWithGooglePublic($text, $targetLanguage, $sourceLanguage);
        if ($translated !== null) {
            return $translated;
        }

        return $text;
    }

    private function translateWithLibreTranslate(string $text, string $targetLanguage, string $sourceLanguage): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://libretranslate.de/translate', [
                'json' => [
                    'q' => $text,
                    'source' => $sourceLanguage,
                    'target' => $targetLanguage,
                    'format' => 'text',
                ],
                'timeout' => 10,
            ]);

            return $this->extractLibreTranslateText($response);
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractLibreTranslateText(ResponseInterface $response): ?string
    {
        $data = $response->toArray(false);
        if (!is_array($data)) {
            return null;
        }

        if (isset($data['translatedText']) && is_string($data['translatedText']) && $data['translatedText'] !== '') {
            return $data['translatedText'];
        }

        if (isset($data['translated_text']) && is_string($data['translated_text']) && $data['translated_text'] !== '') {
            return $data['translated_text'];
        }

        return null;
    }

    private function translateWithGooglePublic(string $text, string $targetLanguage, string $sourceLanguage): ?string
    {
        try {
            $source = $sourceLanguage === 'auto' ? 'auto' : $sourceLanguage;
            $response = $this->httpClient->request('GET', 'https://translate.googleapis.com/translate_a/single', [
                'query' => [
                    'client' => 'gtx',
                    'sl' => $source,
                    'tl' => $targetLanguage,
                    'dt' => 't',
                    'q' => $text,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray(false);
            if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
                return null;
            }

            $parts = [];
            foreach ($data[0] as $chunk) {
                if (is_array($chunk) && isset($chunk[0]) && is_string($chunk[0])) {
                    $parts[] = $chunk[0];
                }
            }

            $translated = trim(implode('', $parts));
            return $translated !== '' ? $translated : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
