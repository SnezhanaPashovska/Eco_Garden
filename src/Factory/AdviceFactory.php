<?php

namespace App\Factory;

use App\Entity\Advice;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class AdviceFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Advice::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'content' => self::faker()->text(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
