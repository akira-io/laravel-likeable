<?php

declare(strict_types=1);

namespace Akira\Likeable\Exceptions;

use Exception;

final class LikeNotFoundException extends Exception
{
    /**
     * The exception is thrown when a like is not found.
     */
    public function __construct()
    {
        $message = __('The like you are trying to remove does not exist.');
        parent::__construct($message);
    }
}
