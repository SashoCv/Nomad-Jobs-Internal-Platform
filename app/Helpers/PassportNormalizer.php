<?php

namespace App\Helpers;

class PassportNormalizer
{
    /**
     * Cyrillic characters that look identical to Latin ones.
     */
    private const CYRILLIC_TO_LATIN = [
        'А' => 'A', 'В' => 'B', 'С' => 'C', 'Е' => 'E', 'Н' => 'H',
        'К' => 'K', 'М' => 'M', 'О' => 'O', 'Р' => 'P', 'Т' => 'T',
        'Х' => 'X', 'У' => 'Y',
        'а' => 'a', 'в' => 'b', 'с' => 'c', 'е' => 'e', 'н' => 'h',
        'к' => 'k', 'м' => 'm', 'о' => 'o', 'р' => 'p', 'т' => 't',
        'х' => 'x', 'у' => 'y',
    ];

    /**
     * Normalize a passport number: convert Cyrillic look-alikes to Latin,
     * uppercase, and strip spaces.
     */
    public static function normalize(?string $passport): ?string
    {
        if ($passport === null || $passport === '') {
            return $passport;
        }

        $normalized = strtr($passport, self::CYRILLIC_TO_LATIN);
        $normalized = strtoupper(str_replace(' ', '', $normalized));

        return $normalized;
    }
}
