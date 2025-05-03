<?php

declare(strict_types=1);

use Akira\Likeable\Tests\Fixtures\Post;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\LazyCollection;

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

it('should attach like status to a model', function (): void {

    $post = Post::query()->create(['name' => fake()->name()]);

    $this->user->like($post);

    $post = $this->user->attachLikeStatus($post);

    expect($post->likesCount())
        ->toBe(1)
        ->and($post->has_liked)
        ->toBeTrue();

});

it('should attach like status to a collection', function (): void {

    $post = Post::query()->create(['name' => fake()->name()]);
    $post2 = Post::query()->create(['name' => fake()->name()]);

    $this->user->like($post);
    $this->user->like($post2);

    $posts = Post::query()->get();

    $posts = $this->user->attachLikeStatus($posts);

    expect($posts)
        ->toHaveCount(2)
        ->and($posts->pluck('has_liked'))
        ->each->toBeTrue();

});

it('should attach like status to a paginator', function (): void {

    $post = Post::query()->create(['name' => fake()->name()]);
    $post2 = Post::query()->create(['name' => fake()->name()]);

    $this->user->like($post);
    $this->user->like($post2);

    $posts = Post::query()->paginate(1);

    $posts = $this->user->attachLikeStatus($posts);

    expect($posts)
        ->toHaveCount(1)
        ->and($posts->pluck('has_liked'))
        ->each->toBeTrue();

})->with([
    'lazy' => [LazyCollection::class],
    'paginator' => [Paginator::class],
    'abstract_paginator' => [AbstractPaginator::class],
    'abstract_cursor_paginator' => [AbstractCursorPaginator::class],
]);
