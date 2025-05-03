<?php

declare(strict_types=1);

namespace Akira\Likeable\Tests\Fixtures;

use Akira\Likeable\Concerns\Likeable;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final class Post extends Model
{
    use Likeable;

    protected $fillable = ['name'];
}
