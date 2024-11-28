<?php

namespace App\Factory;

use App\Entity\Month;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class MonthFactory extends PersistentProxyObjectFactory
{
    private const MONTHS = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    public function __construct()
    {
    }

    public static function class (): string
    {
        return Month::class;
    }

    protected function defaults(): array | callable
    {
        return [
            'month_number' => self::faker()->numberBetween(1, 12),
            'name' => self::faker()->randomElement(self::MONTHS),
        ];
    }

    /**
     * Creates a specific month by number.
     */
    public static function createSpecificMonth(int $number): Month
    {
        return self::new ([
            'month_number' => $number,
            'name' => self::MONTHS[$number],
        ])->create();
    }

    protected function initialize(): static
    {
        return $this;
    }
}
