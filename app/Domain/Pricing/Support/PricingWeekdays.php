<?php

namespace App\Domain\Pricing\Support;

/**
 * Support class per la gestione dei giorni della settimana
 * nel dominio pricing.
 *
 * Convenzione adottata:
 * - 1 = lunedì
 * - 7 = domenica
 */
final class PricingWeekdays
{
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    /**
     * Costruttore privato per impedire istanziazione.
     */
    private function __construct()
    {
    }

    /**
     * Restituisce tutti i valori ammessi.
     *
     * @return array<int, int>
     */
    public static function values(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }

    /**
     * Restituisce opzioni pronte per select o checkbox list.
     *
     * @return array<int, array<string, int|string>>
     */
    public static function options(): array
    {
        return [
            ['value' => self::MONDAY, 'label' => 'Lunedì'],
            ['value' => self::TUESDAY, 'label' => 'Martedì'],
            ['value' => self::WEDNESDAY, 'label' => 'Mercoledì'],
            ['value' => self::THURSDAY, 'label' => 'Giovedì'],
            ['value' => self::FRIDAY, 'label' => 'Venerdì'],
            ['value' => self::SATURDAY, 'label' => 'Sabato'],
            ['value' => self::SUNDAY, 'label' => 'Domenica'],
        ];
    }

    /**
     * Restituisce la label leggibile di un giorno.
     */
    public static function label(int|null $day): string
    {
        return match ($day) {
            self::MONDAY => 'Lunedì',
            self::TUESDAY => 'Martedì',
            self::WEDNESDAY => 'Mercoledì',
            self::THURSDAY => 'Giovedì',
            self::FRIDAY => 'Venerdì',
            self::SATURDAY => 'Sabato',
            self::SUNDAY => 'Domenica',
            default => '',
        };
    }

    /**
     * Verifica se il giorno è valido.
     */
    public static function isValid(int $day): bool
    {
        return in_array($day, self::values(), true);
    }
}