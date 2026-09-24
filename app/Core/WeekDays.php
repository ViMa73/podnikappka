<?php
namespace Core;

class WeekDays
{
    /**
     * Interní mapování (pro bitmasku)
     */
    public static array $days = [
        'mon' => 1,
        'tue' => 2,
        'wed' => 4,
        'thu' => 8,
        'fri' => 16,
        'sat' => 32,
        'sun' => 64,
    ];

    /**
     * České zkratky pro UI
     */
    public static array $labels = [
        'mon' => 'Po',
        'tue' => 'Út',
        'wed' => 'St',
        'thu' => 'Čt',
        'fri' => 'Pá',
        'sat' => 'So',
        'sun' => 'Ne',
    ];

    /**
     * Česká plná jména (do budoucna)
     */
    public static array $names = [
        'mon' => 'Pondělí',
        'tue' => 'Úterý',
        'wed' => 'Středa',
        'thu' => 'Čtvrtek',
        'fri' => 'Pátek',
        'sat' => 'Sobota',
        'sun' => 'Neděle',
    ];

    public static function toMask(array $input): int
    {
        $mask = 0;
        foreach (self::$days as $key => $bit) {
            if (!empty($input[$key])) {
                $mask |= $bit;
            }
        }
        return $mask;
    }

    public static function isChecked(int $mask, int $bit): bool
    {
        return ($mask & $bit) === $bit;
    }
}
