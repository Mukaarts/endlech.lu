<?php

namespace App\Twig;

use App\Entity\Restaurant;
use App\Service\OpeningHoursService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OpeningHoursExtension extends AbstractExtension
{
    public function __construct(private readonly OpeningHoursService $openingHoursService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('is_open_now', [$this, 'isOpenNow']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('next_opening_time', [$this, 'getNextOpeningTime']),
        ];
    }

    public function isOpenNow(Restaurant $restaurant): bool
    {
        return $this->openingHoursService->isOpenNow($restaurant);
    }

    /**
     * @return array{dayOfWeek: int, time: \DateTimeInterface}|null
     */
    public function getNextOpeningTime(Restaurant $restaurant): ?array
    {
        return $this->openingHoursService->getNextOpeningTime($restaurant);
    }
}
