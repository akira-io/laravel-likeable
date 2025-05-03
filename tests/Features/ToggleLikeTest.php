<?php

declare(strict_types=1);

use Akira\Likeable\Tests\Fixtures\Post;

beforeEach(function (): void {
    $this->user = user();
});

it('can toggle like a post', function (): void {
    $post = Post::query()->create([
        'name' => fake()->name(),
    ]);

    expect($post->likesCount())
        ->toBe(0);

    $this->user->toggleLike($post);

    expect($post->likesCount())
        ->toBe(1)
        ->and($post->likers()->count())
        ->toBe(1);

    $this->user->toggleLike($post);

    expect($post->likesCount())
        ->toBe(0)
        ->and($post->likers()->count())
        ->toBe(0);
});
