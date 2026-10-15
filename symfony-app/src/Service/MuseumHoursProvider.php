<?php

namespace App\Service;

/**
 * Builds `#/components/schemas/MuseumHours`.
 *
 * The museum is open 09:00–18:00 Monday to Thursday, 10:00–16:00 on Friday,
 * and closed at weekends. Each page holds `$limit` opening days.
 */
class MuseumHoursProvider
{
    private const array HOURS_BY_WEEKDAY = [
        1 => ['09:00', '18:00'],
        2 => ['09:00', '18:00'],
        3 => ['09:00', '18:00'],
        4 => ['09:00', '18:00'],
        5 => ['10:00', '16:00'],
    ];

    /**
     * @return list<array{date: string, timeOpen: string, timeClose: string}>
     */
    public function getHours(\DateTimeImmutable $startDate, int $page, int $limit): array
    {
        $skip = ($page - 1) * $limit;
        $hours = [];

        for ($date = $startDate; \count($hours) < $limit; $date = $date->modify('+1 day')) {
            $opening = self::HOURS_BY_WEEKDAY[(int) $date->format('N')] ?? null;
            if (null === $opening) {
                continue;
            }
            if ($skip > 0) {
                --$skip;
                continue;
            }

            $hours[] = ['date' => $date->format('Y-m-d'), 'timeOpen' => $opening[0], 'timeClose' => $opening[1]];
        }

        return $hours;
    }
}
