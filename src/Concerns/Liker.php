<?php

declare(strict_types=1);

namespace Akira\Likeable\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Liker
{
    /**
     * Like the given model.
     */
    public function like(Model $model): bool
    {

        $likeableModel = resolve(config('likeable.model'));

        return (bool) $likeableModel->likeable()->create($this->getLikeAttributes($model));

    }

    /**
     * @throws Exception
     */
    public function unlike(Model $model): bool
    {

        $likeableModel = resolve('likeable.model');

        $likeableRecord = $this->getLikeableQuery($likeableModel, $model)->first();

        return (bool) $likeableRecord?->delete();
    }

    /**
     * Get the likes for the model.
     *
     * @return HasMany<Model, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(
            resolve('likeable.model'),
            resolve('likeable.user_foreign_key'),
            $this->getKeyName()
        );
    }

    /**
     * Toggle the like status of the model.
     */
    public function toggleLike(Model $model): bool
    {
        $likeableModel = resolve('likeable.model');

        $likeableRecord = $this->getLikeableQuery($likeableModel, $model)->first();

        if ($likeableRecord) {
            return (bool) $likeableRecord->delete();
        }

        return $this->like($model);
    }

    /**
     * Extracted method to construct the query for fetching likeable records.
     */
    private function getLikeableQuery($likeableModel, Model $model)
    {

        return $likeableModel
            ->where('likeable_id', $model->getKey())
            ->where('likeable_type', $model->getMorphClass())
            ->where(resolve('likeable.user_foreign_key'), $this->getKey());
    }

    /**
     * Get the attributes for the like operation.
     */
    private function getLikeAttributes(Model $model): array
    {

        return [
            'likeable_type' => $model->getMorphClass(),
            'likeable_id' => $model->getKey(),
            config('likeable.user_foreign_key') => $this->getKey(),
        ];
    }
}
