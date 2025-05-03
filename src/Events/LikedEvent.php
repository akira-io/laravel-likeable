<?php

declare(strict_types=1);

namespace Akira\Likeable\Events;

use Akira\Likeable\Likeable;
use Illuminate\Foundation\Events\Dispatchable;

final class LikedEvent
{
    use Dispatchable;

    /**
     * The event is dispatched when a like is created.
     */
    public function __construct(public Likeable $likeable) {}
}
