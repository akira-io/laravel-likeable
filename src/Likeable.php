<?php

declare(strict_types=1);

namespace Akira\Likeable;

use Akira\Likeable\Events\LikedEvent;
use Akira\Likeable\Events\UnLikedEvent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

final class Likeable extends Model
{
    protected $table = 'likeable';

    /*
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'likeable_id',
        'likeable_type',
        'user_id',
    ];

    /**
     * Get the table associated with the model.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = type(config('likeable.table', 'likeable'))->asString();

        parent::__construct($attributes);
    }

    /**
     *Get all the owning likeable models.
     *
     * @return MorphTo<Model, $this>
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that the likeable belongs to.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            config('likeable.user_foreign_key', 'user_id'),
        );
    }

    /**
     * Get the user dispatches events for the model.
     *
     * @return array<string, class-string>
     */
    public function getDispatchesEvents(): array
    {

        return [
            'created' => LikedEvent::class,
            'deleted' => UnLikedEvent::class,
        ];
    }

    /**
     * Get the likeable that the likeable belongs to.
     */
    #[Scope]
    public function withType(Builder $query, string $type): Builder
    {

        return $query->where('likeable_type', resolve($type)->getMorphClass());
    }

    /**
     * Get the likeable that the likeable belongs to.
     *
     * @return BelongsTo<Model, $this>
     */
    public function liker(): BelongsTo
    {
        return $this->user();

    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
        self::saving(function (Likeable $likeable): void {
            $userForeignKey = config('likeable.user_foreign_key', 'user_id');
            $likeable->setAttribute($userForeignKey, $likeable->{$userForeignKey} ?: auth()->id());

            if (config('likeable.uuids')) {
                $likeable->setAttribute($likeable->getKeyName(), $likeable->{$likeable->getKeyName()} ?: (string)
                Str::orderedUuid());
            }
        });
    }
}
