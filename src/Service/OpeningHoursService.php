<?php

namespace App\Service;

use App\Entity\Restaurant;

class OpeningHoursService
{
    private const TIMEZONE = 'Europe/Luxembourg';

    public function isOpenNow(Restaurant $restaurant): bool
    {
        return $this->isOpenAt($restaurant, new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE)));
    }

    public function isOpenAt(Restaurant $restaurant, \DateTimeInterface $dateTime): bool
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $dt = \DateTimeImmutable::createFromInterface($dateTime)->setTimezone($tz);

        $currentDay = (int) $dt->format('N');
        $currentTime = $dt->format('H:i:s');

        $todayEntry = $restaurant->getOpeningHourForDay($currentDay);

        if ($todayEntry !== null && !$todayEntry->isClosed() && $todayEntry->getOpenTime() !== null && $todayEntry->getCloseTime() !== null) {
            $open = $todayEntry->getOpenTime()->format('H:i:s');
            $close = $todayEntry->getCloseTime()->format('H:i:s');

            if ($open <= $close) {
                if ($currentTime >= $open && $currentTime < $close) {
                    return true;
                }
            } else {
                if ($currentTime >= $open) {
                    return true;
                }
            }
        }

        $previousDay = $currentDay === 1 ? 7 : $currentDay - 1;
        $yesterdayEntry = $restaurant->getOpeningHourForDay($previousDay);

        if ($yesterdayEntry !== null && !$yesterdayEntry->isClosed() && $yesterdayEntry->getOpenTime() !== null && $yesterdayEntry->getCloseTime() !== null) {
            $open = $yesterdayEntry->getOpenTime()->format('H:i:s');
            $close = $yesterdayEntry->getCloseTime()->format('H:i:s');

            if ($open > $close && $currentTime < $close) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{dayOfWeek: int, time: \DateTimeInterface}|null
     */
    public function getNextOpeningTime(Restaurant $restaurant): ?array
    {
        if ($this->isOpenNow($restaurant)) {
            return null;
        }

        $tz = new \DateTimeZone(self::TIMEZONE);
        $now = new \DateTimeImmutable('now', $tz);
        $currentDay = (int) $now->format('N');
        $currentTime = $now->format('H:i:s');

        $todayEntry = $restaurant->getOpeningHourForDay($currentDay);
        if ($todayEntry !== null && !$todayEntry->isClosed() && $todayEntry->getOpenTime() !== null) {
            $openTime = $todayEntry->getOpenTime()->format('H:i:s');
            if ($openTime > $currentTime) {
                return [
                    'dayOfWeek' => $currentDay,
                    'time' => $todayEntry->getOpenTime(),
                ];
            }
        }

        for ($i = 1; $i <= 6; ++$i) {
            $day = (($currentDay - 1 + $i) % 7) + 1;
            $entry = $restaurant->getOpeningHourForDay($day);
            if ($entry !== null && !$entry->isClosed() && $entry->getOpenTime() !== null) {
                return [
                    'dayOfWeek' => $day,
                    'time' => $entry->getOpenTime(),
                ];
            }
        }

        return null;
    }
}
