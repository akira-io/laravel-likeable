<?php

declare(strict_types=1);

namespace Akira\Likeable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\Likeable\Likeable
 */
final class Likeable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * This method is used by the facade to resolve the underlying instance.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Likeable\Likeable::class;
    }
}
