<?php

declare(strict_types=1);

use Akira\Likeable\Tests\Fixtures\Post;

beforeEach(function (): void {
    $this->user = user();
});

it('can unlike a post', function (): void {

    $post = Post::query()->create([
        'name' => fake()->name(),
    ]);

    $this->user->like($post);

    expect($post->likesCount())
        ->toBe(1);

    $this->user->unlike($post);

    expect($post->likesCount())
        ->toBe(0)
        ->and($post->likers()->count())
        ->toBe(0);

});
