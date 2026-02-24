<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function ask(string $message): ?string
    {
        $message = trim($message);
        if ($message === '') {
            return null;
        }

        $baseUrl = $this->getEnvValue('OLLAMA_BASE_URL');
        if ($baseUrl === '') {
            $baseUrl = 'http://localhost:11434';
        }

        $model = $this->getEnvValue('OLLAMA_MODEL');
        if ($model === '') {
            $model = 'llama3:latest';
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($baseUrl, '/') . '/api/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $message,
                    'stream' => false,
                ],
                'timeout' => 20,
            ]);

            $data = $response->toArray(false);
            if (!isset($data['response']) || !is_string($data['response'])) {
                return null;
            }

            $content = trim($data['response']);
            if ($content === '') {
                return null;
            }

            return '[Bot IA] ' . $content;
        } catch (\Throwable) {
            return null;
        }
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}
