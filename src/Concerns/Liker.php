<?php

declare(strict_types=1);

namespace Akira\Likeable\Concerns;

use Closure;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

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
            $this->getKeyName(),
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
     * Check if the user has liked the model and return the status.
     */
    public function attachLikeStatus(Model|Collection|LazyCollection|Paginator|AbstractPaginator|AbstractCursorPaginator &$entities, ?Closure $resolver = null): Model|Collection|LazyCollection|Paginator|AbstractPaginator|AbstractCursorPaginator
    {

        $likes = $this->getLikesByTypeAndId();

        $resolver ??= fn ($entity) => $entity;

        $enhanceEntityWithLikes = fn ($entity) => $this->enhanceEntityWithLikes($entity, $likes, $resolver);

        return $this->applyEnhancerToEntities($entities, $enhanceEntityWithLikes);
    }

    /**
     * Get the likes by type and ID.
     */
    private function getLikesByTypeAndId(): Collection
    {

        return $this->likes()
            ->get();
    }

    /**
     * Enhance the entity with like status.
     */
    private function enhanceEntityWithLikes($entity, Collection $likes, Closure $resolver): mixed
    {

        $entity = $resolver($entity);

        if ($this->isLikeableEntity($entity)) {
            $entity->setAttribute('has_liked', $this->hasLiked($entity, $likes));
        }

        return $entity;
    }

    /**
     * Apply the enhancer to the entities.
     */
    private function applyEnhancerToEntities(
        Model|Collection|LazyCollection|Paginator|AbstractPaginator|AbstractCursorPaginator|array $entities,
        Closure $enhanceEntityWithLikes,
    ): Model|Collection|LazyCollection|Paginator|AbstractPaginator|AbstractCursorPaginator {

        return match (true) {
            $entities instanceof Model => $enhanceEntityWithLikes($entities),
            $entities instanceof Collection => $entities->each($enhanceEntityWithLikes),
            $entities instanceof LazyCollection => $entities->map($enhanceEntityWithLikes),
            $entities instanceof AbstractPaginator,
            $entities instanceof AbstractCursorPaginator => $entities->through($enhanceEntityWithLikes),
            $entities instanceof Paginator => collect($entities->items())->map($enhanceEntityWithLikes),
            default => collect($entities)->each($enhanceEntityWithLikes),
        };
    }

    /**
     * Check if the entity is likeable.
     */
    private function isLikeableEntity($entity): bool
    {

        return is_object((object) $entity) && in_array(Likeable::class, class_uses_recursive((object) $entity));
    }

    /**
     * Check if the user has liked the entity.
     */
    private function hasLiked(Model $entity, Collection $likes): bool
    {

        return $likes->contains(fn ($like): bool => $like->likeable_type === $entity->getMorphClass()
            && $like->likeable_id === $entity->getKey());
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
