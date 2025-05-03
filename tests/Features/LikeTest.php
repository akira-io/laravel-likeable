<?php

declare(strict_types=1);

use Akira\Likeable\Tests\Fixtures\Post;

beforeEach(function (): void {
    $this->user = user();
});

it('can like a post', function (): void {

    $post = Post::query()->create([
        'name' => fake()->name(),
    ]);

    $this->user->like($post);

    expect($post->likesCount())
        ->toBe(1)
        ->and($post->likers()->first()->id)
        ->toBe($this->user->id);

});

it('should attach like status', function () {

    $post = Post::query()->create(['name' => fake()->name()]);

    $this->user->like($post);

    $post = $this->user->attachLikeStatus($post);

    expect($post->likesCount())
        ->toBe(1)
        ->and($post->has_liked)
        ->toBeTrue();

});
