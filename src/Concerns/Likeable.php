<?php

declare(strict_types=1);

namespace Akira\Likeable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait Likeable
{
    /**
     * Get all of the likes for the model.
     *
     * @return MorphMany<Model, $this>
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(config('likeable.model'), 'likeable');
    }

    /**
     * Get all likers for the model.
     *
     * @return Collection<Model>
     */
    public function likers(): Collection
    {

        return $this->likes()->with('liker')->get()->pluck('liker');
    }

    /**
     * Get all likes count for the model.
     */
    public function likesCount(): int
    {
        return $this->likes()->count();
    }
}
