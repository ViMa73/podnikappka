<?php

namespace Core;

final class CzechHolidays
{
    public static function isHoliday(string|\DateTimeInterface $date): bool
    {
        return self::getHolidayName($date) !== null;
    }

    public static function getHolidayName(string|\DateTimeInterface $date): ?string
    {
        $d = $date instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($date)
            : new \DateTimeImmutable($date);

        $ymd = $d->format('Y-m-d');
        $year = (int)$d->format('Y');

        $fixed = [
            "{$year}-01-01" => 'Den obnovy samostatného českého státu / Nový rok',
            "{$year}-05-01" => 'Svátek práce',
            "{$year}-05-08" => 'Den vítězství',
            "{$year}-07-05" => 'Den slovanských věrozvěstů Cyrila a Metoděje',
            "{$year}-07-06" => 'Den upálení mistra Jana Husa',
            "{$year}-09-28" => 'Den české státnosti',
            "{$year}-10-28" => 'Den vzniku samostatného československého státu',
            "{$year}-11-17" => 'Den boje za svobodu a demokracii',
            "{$year}-12-24" => 'Štědrý den',
            "{$year}-12-25" => '1. svátek vánoční',
            "{$year}-12-26" => '2. svátek vánoční',
        ];

        if (isset($fixed[$ymd])) {
            return $fixed[$ymd];
        }

        $easterSunday = self::easterSunday($year);
        $goodFriday = $easterSunday->modify('-2 days')->format('Y-m-d');
        $easterMonday = $easterSunday->modify('+1 day')->format('Y-m-d');

        if ($ymd === $goodFriday) {
            return 'Velký pátek';
        }

        if ($ymd === $easterMonday) {
            return 'Velikonoční pondělí';
        }

        return null;
    }

    public static function getYearHolidays(int $year): array
    {
        $dates = [
            "{$year}-01-01",
            "{$year}-05-01",
            "{$year}-05-08",
            "{$year}-07-05",
            "{$year}-07-06",
            "{$year}-09-28",
            "{$year}-10-28",
            "{$year}-11-17",
            "{$year}-12-24",
            "{$year}-12-25",
            "{$year}-12-26",
        ];

        $easterSunday = self::easterSunday($year);
        $dates[] = $easterSunday->modify('-2 days')->format('Y-m-d');
        $dates[] = $easterSunday->modify('+1 day')->format('Y-m-d');

        sort($dates);

        $result = [];
        foreach ($dates as $date) {
            $result[$date] = self::getHolidayName($date);
        }

        return $result;
    }

    private static function easterSunday(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
}
