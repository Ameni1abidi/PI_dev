<?php

namespace App\Service;

use App\Entity\Forum;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiForumAssistantService
{
    private const BOT_PREFIX = '[Bot IA] ';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function getBotPrefix(): string
    {
        return self::BOT_PREFIX;
    }

    public function generateReply(Forum $forum, string $userComment): ?string
    {
        $apiKey = $this->getEnvValue('OPENAI_API_KEY');
        if ($apiKey === '') {
            return null;
        }

        $userComment = trim($userComment);
        if ($userComment === '') {
            return null;
        }

        $model = $this->getEnvValue('OPENAI_FORUM_MODEL');
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $messages = [
            [
                'role' => 'system',
                'content' => 'Tu es un assistant utile pour un forum scolaire EduFlex. Reponds en francais, de facon concise, polie et factuelle. Si la question est hors sujet scolaire, recadre brievement.',
            ],
            [
                'role' => 'user',
                'content' => sprintf(
                    "Sujet du forum: %s\nType: %s\nDescription du sujet: %s\n\nQuestion/commentaire de l'utilisateur: %s",
                    (string) $forum->getTitre(),
                    (string) $forum->getType(),
                    (string) $forum->getContenu(),
                    $userComment
                ),
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 220,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray(false);
            if (
                !isset($data['choices'][0]['message']['content']) ||
                !is_string($data['choices'][0]['message']['content'])
            ) {
                return null;
            }

            $content = trim($data['choices'][0]['message']['content']);
            if ($content === '') {
                return null;
            }

            return self::BOT_PREFIX . $content;
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
