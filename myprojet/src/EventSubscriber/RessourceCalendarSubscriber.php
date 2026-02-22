<?php

namespace App\EventSubscriber;

use App\Repository\RessourceRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RessourceCalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RessourceRepository $ressourceRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent): void
    {
        $filters = $setDataEvent->getFilters();
        $chapitreId = isset($filters['chapitreId']) && (int) $filters['chapitreId'] > 0
            ? (int) $filters['chapitreId']
            : null;
        $categorie = isset($filters['categorie']) ? trim((string) $filters['categorie']) : null;

        $ressources = $this->ressourceRepository->findCalendarResources(
            $setDataEvent->getStart(),
            $setDataEvent->getEnd(),
            $chapitreId,
            $categorie
        );

        foreach ($ressources as $ressource) {
            $start = \DateTime::createFromInterface($ressource->getAvailableAt() ?? new \DateTimeImmutable());
            $categoryName = (string) ($ressource->getCategorie()?->getNom() ?? 'inconnue');

            $calendarEvent = new Event(
                $ressource->getTitre() ?? 'Ressource',
                $start
            );

            $calendarEvent->setOptions([
                'id' => (string) $ressource->getId(),
                'backgroundColor' => $this->resolveColorByCategory($categoryName),
                'borderColor' => '#1f2937',
                'extendedProps' => [
                    'ressourceId' => $ressource->getId(),
                    'score' => $ressource->getScore(),
                    'likes' => $ressource->getNbLikes(),
                    'favoris' => $ressource->getNbFavoris(),
                    'vues' => $ressource->getNbVues(),
                    'badge' => $ressource->getBadge(),
                    'categorie' => $categoryName,
                ],
            ]);

            $setDataEvent->addEvent($calendarEvent);
        }
    }

    private function resolveColorByCategory(string $category): string
    {
        return match (strtolower($category)) {
            'video' => '#dbeafe',
            'audio' => '#dcfce7',
            'image' => '#fef3c7',
            'lien' => '#e9d5ff',
            default => '#e5e7eb',
        };
    }
}

