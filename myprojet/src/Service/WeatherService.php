<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private ?string $lastError = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentWeather(string $city): ?array
    {
        $this->lastError = null;
        $city = trim($city);
        if ($city === '') {
            $this->lastError = 'Ville vide.';
            return null;
        }

        $apiKey = $this->getEnvValue('WEATHER_API_KEY');
        if ($apiKey === '') {
            $apiKey = $this->getEnvValue('WEATHERAPI_KEY');
        }
        if ($apiKey === '') {
            $this->lastError = 'WEATHER_API_KEY (ou WEATHERAPI_KEY) non configuree.';
            return null;
        }

        try {
            $data = $this->requestForecast($apiKey, $city, 7);
            if (!$this->isValidWeatherApiPayload($data)) {
                // Fallback for plans that don't allow 7 days forecast.
                $data = $this->requestForecast($apiKey, $city, 3);
                if (!$this->isValidWeatherApiPayload($data)) {
                    $this->hydrateApiError($data);

                    return null;
                }
            }

            return $this->normalizeWeatherPayload($data);
        } catch (\Throwable $e) {
            $this->lastError = 'Erreur reseau: ' . $e->getMessage();
            return null;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestForecast(string $apiKey, string $city, int $days): ?array
    {
        $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/forecast.json', [
            'query' => [
                'key' => $apiKey,
                'q' => $city,
                'lang' => 'fr',
                'days' => $days,
                'aqi' => 'no',
                'alerts' => 'no',
            ],
            'timeout' => 10,
        ]);

        $data = $response->toArray(false);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function isValidWeatherApiPayload(?array $data): bool
    {
        return is_array($data)
            && isset($data['location']['name'], $data['current']['temp_c'], $data['current']['condition']['text']);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function hydrateApiError(?array $data): void
    {
        $errorCode = (string) ($data['error']['code'] ?? '');
        $errorMessage = (string) ($data['error']['message'] ?? '');

        if ($errorCode !== '' || $errorMessage !== '') {
            $this->lastError = 'Erreur WeatherAPI: ' . trim($errorCode . ' ' . $errorMessage);

            return;
        }

        $this->lastError = 'Reponse meteo invalide.';
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeWeatherPayload(array $data): array
    {
        $forecastDays = [];
        $forecastRows = (array) ($data['forecast']['forecastday'] ?? []);
        foreach ($forecastRows as $row) {
            if (!is_array($row) || !isset($row['date'], $row['day']['maxtemp_c'], $row['day']['mintemp_c'])) {
                continue;
            }

            $forecastDays[] = [
                'date' => (string) $row['date'],
                'max_temp' => (float) $row['day']['maxtemp_c'],
                'min_temp' => (float) $row['day']['mintemp_c'],
                'avg_humidity' => (int) ($row['day']['avghumidity'] ?? 0),
                'description' => (string) ($row['day']['condition']['text'] ?? ''),
                'icon' => isset($row['day']['condition']['icon'])
                    ? ('https:' . (string) $row['day']['condition']['icon'])
                    : null,
            ];
        }

        return [
            'name' => (string) $data['location']['name'],
            'country' => (string) ($data['location']['country'] ?? ''),
            'localtime' => (string) ($data['location']['localtime'] ?? ''),
            'main' => [
                'temp' => (float) $data['current']['temp_c'],
                'feels_like' => (float) ($data['current']['feelslike_c'] ?? $data['current']['temp_c']),
                'humidity' => (int) ($data['current']['humidity'] ?? 0),
            ],
            'weather' => [
                [
                    'description' => (string) $data['current']['condition']['text'],
                    'icon' => isset($data['current']['condition']['icon'])
                        ? ('https:' . (string) $data['current']['condition']['icon'])
                        : null,
                ],
            ],
            'wind_kph' => (float) ($data['current']['wind_kph'] ?? 0),
            'forecast_days' => $forecastDays,
        ];
    }
}
