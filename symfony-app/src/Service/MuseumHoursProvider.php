<?php

namespace App\Service;

/**
 * Builds `#/components/schemas/MuseumHours`.
 *
 * The museum is open 09:00–18:00 Monday to Thursday, 10:00–16:00 on Friday,
 * and closed at weekends. Pages are counted in opening days.
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

    /** Every 7 consecutive days contain this many opening days. */
    private const int OPENING_DAYS_PER_WEEK = 5;

    /** `format: date` allows four-digit years only. */
    private const string LAST_DATE = '9999-12-31';

    /**
     * @param int $offset number of opening days to skip
     *
     * @return list<array{date: string, timeOpen: string, timeClose: string}>
     */
    public function getHours(\DateTimeImmutable $startDate, int $offset, int $limit): array
    {
        $lastDate = new \DateTimeImmutable(self::LAST_DATE);

        // Jump whole weeks instead of stepping through every skipped day.
        $weeks = intdiv($offset, self::OPENING_DAYS_PER_WEEK);
        if ($weeks > intdiv($startDate->diff($lastDate)->days, 7)) {
            return [];
        }
        $date = $startDate->modify(\sprintf('+%d days', $weeks * 7));
        $skip = $offset % self::OPENING_DAYS_PER_WEEK;

        $hours = [];
        for (; \count($hours) < $limit && $date <= $lastDate; $date = $date->modify('+1 day')) {
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
