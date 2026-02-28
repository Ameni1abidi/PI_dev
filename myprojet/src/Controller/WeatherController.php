<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'app_weather', methods: ['GET'])]
    #[Route('/{context}/weather', name: 'app_weather_context', requirements: ['context' => 'student|eleve|enseignant'], methods: ['GET'])]
    public function index(Request $request, WeatherService $weatherService, ?string $context = null): Response
    {
        $city = trim((string) $request->query->get('city', 'Tunis'));
        $weather = $weatherService->getCurrentWeather($city);
        $weatherError = $weatherService->getLastError();

        $baseTemplate = $this->getWeatherBaseTemplate($context);
        $template = $baseTemplate ? 'weather/index_shell.html.twig' : 'weather/index.html.twig';

        return $this->render($template, [
            'weather' => $weather,
            'weather_error' => $weatherError,
            'city' => $city,
            'base_template' => $baseTemplate,
        ]);
    }

    private function getWeatherBaseTemplate(?string $context): ?string
    {
        return match ($context) {
            'student', 'eleve' => 'student/base.html.twig',
            'enseignant' => 'crud_base.html.twig',
            default => null,
        };
    }
}
