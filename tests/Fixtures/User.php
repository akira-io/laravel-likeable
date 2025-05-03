<?php

declare(strict_types=1);

namespace Akira\Likeable\Tests\Fixtures;

use Akira\Likeable\Concerns\Liker;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final class User extends Model
{
    use Liker;

    protected $fillable = ['name'];
}
