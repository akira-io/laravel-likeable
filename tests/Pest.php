<?php

declare(strict_types=1);

use Akira\Likeable\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

function user(): Akira\Likeable\Tests\Fixtures\User
{

    return Akira\Likeable\Tests\Fixtures\User::query()->create([
        'name' => fake()->name(),
    ]);

}
