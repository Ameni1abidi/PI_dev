<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherService
{
    private const API_URLS = [
        'https://api.open-meteo.com/v1/forecast',
        'http://api.open-meteo.com/v1/forecast',
    ];

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getTodayWeather(): array
    {
        foreach (self::API_URLS as $apiUrl) {
            try {
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => [
                        'latitude' => 36.8065,
                        'longitude' => 10.1815,
                        'current' => 'temperature_2m,weather_code',
                        'timezone' => 'auto',
                    ],
                    'timeout' => 8,
                ]);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $data = $response->toArray(false);
                $current = $data['current'] ?? [];
                $time = isset($current['time']) ? new \DateTimeImmutable($current['time']) : new \DateTimeImmutable('today');

                return [
                    'city' => 'Tunis',
                    'date' => $time->format('d/m/Y'),
                    'temperature' => isset($current['temperature_2m']) ? (float) $current['temperature_2m'] : null,
                    'description' => $this->mapWeatherCode((int) ($current['weather_code'] ?? -1)),
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return [
            'city' => 'Tunis',
            'date' => (new \DateTimeImmutable('today'))->format('d/m/Y'),
            'temperature' => null,
            'description' => 'Indisponible',
        ];
    }

    private function mapWeatherCode(int $code): string
    {
        return match ($code) {
            0 => 'Ciel degage',
            1, 2, 3 => 'Partiellement nuageux',
            45, 48 => 'Brouillard',
            51, 53, 55 => 'Bruine',
            61, 63, 65 => 'Pluie',
            71, 73, 75 => 'Neige',
            80, 81, 82 => 'Averses',
            95, 96, 99 => 'Orage',
            default => 'Inconnu',
        };
    }
}
