# Relationships

Laravel Likeable establishes several Eloquent relationships between your models and the like records. Understanding these relationships helps you query and manipulate like data effectively.

## Likeable Model Relationships

The `Akira\Likeable\Likeable` model is the pivot model that stores each like record. It defines relationships to both the user who liked and the model that was liked.

### likeable() - MorphTo

A polymorphic relationship to the liked model.

```php
$like = Likeable::first();
$model = $like->likeable;

// $model could be Post, Comment, or any model using the Likeable trait
```

This is the inverse of the polymorphic relationship. The liked model can be any type.

### user() - BelongsTo

Relationship to the user who created the like.

```php
$like = Likeable::first();
$user = $like->user;

echo $user->name; // "John Doe"
```

The foreign key defaults to `user_id` but can be customized via config.

### liker() - Alias for user()

An alias for better semantic clarity:

```php
$like = Likeable::first();
$liker = $like->liker;

// Same as $like->user
```

Both methods return the same relationship.

## Likeable Trait Relationships

Models using the `Akira\Likeable\Concerns\Likeable` trait gain like-related relationships.

### likes() - MorphMany

A polymorphic relationship to all like records for this model.

```php
use App\Models\Post;

$post = Post::find(1);
$likes = $post->likes;

// Collection of Likeable models
foreach ($likes as $like) {
    echo $like->user->name;
}
```

This returns a `MorphMany` relationship, so you can use standard query builder methods:

```php
// Count likes
$count = $post->likes()->count();

// Recent likes
$recentLikes = $post->likes()
    ->where('created_at', '>', now()->subDays(7))
    ->get();

// Eager load user
$likes = $post->likes()->with('user')->get();
```

### likers() - Collection

Returns a collection of users who have liked the model.

```php
$post = Post::find(1);
$likers = $post->likers();

// Collection of User models
foreach ($likers as $user) {
    echo $user->name;
}
```

Internally, this executes:

```php
$this->likes()->with('liker')->get()->pluck('liker');
```

It's a convenience method, not a traditional Eloquent relationship. Use it when you need the user models directly.

## Liker Trait Relationships

Models using the `Akira\Likeable\Concerns\Liker` trait (typically your User model) gain relationships to their likes.

### likes() - HasMany

Relationship to all likes created by the user.

```php
use App\Models\User;

$user = User::find(1);
$likes = $user->likes;

// Collection of Likeable models
foreach ($likes as $like) {
    $likedModel = $like->likeable;
    echo get_class($likedModel); // "App\Models\Post"
}
```

Query the relationship:

```php
// Count user's likes
$count = $user->likes()->count();

// User's likes on posts
$postLikes = $user->likes()
    ->where('likeable_type', Post::class)
    ->get();

// User's recent likes
$recentLikes = $user->likes()
    ->where('created_at', '>', now()->subWeek())
    ->with('likeable')
    ->get();
```

## Querying Relationships

### Find Posts Liked by a User

```php
$user = User::find(1);

$likedPostIds = $user->likes()
    ->where('likeable_type', Post::class)
    ->pluck('likeable_id');

$likedPosts = Post::whereIn('id', $likedPostIds)->get();
```

Or use eager loading:

```php
$likes = $user->likes()
    ->where('likeable_type', Post::class)
    ->with('likeable')
    ->get();

$likedPosts = $likes->pluck('likeable');
```

### Find Users Who Liked a Post

```php
$post = Post::find(1);

$likerIds = $post->likes()->pluck('user_id');
$users = User::whereIn('id', $likerIds)->get();

// Or use the convenience method
$users = $post->likers();
```

### Posts with Specific Like Count

```php
$posts = Post::has('likes', '>=', 10)->get();
```

### Posts Liked by Authenticated User

```php
$user = auth()->user();

$likedPostIds = $user->likes()
    ->where('likeable_type', Post::class)
    ->pluck('likeable_id');

$posts = Post::whereIn('id', $likedPostIds)->get();
```

### Most Liked Posts

```php
$posts = Post::withCount('likes')
    ->orderByDesc('likes_count')
    ->take(10)
    ->get();

foreach ($posts as $post) {
    echo "{$post->title}: {$post->likes_count} likes";
}
```

## Eager Loading

Prevent N+1 queries by eager loading relationships.

### Load Likers with Posts

```php
$posts = Post::with('likes.user')->get();

foreach ($posts as $post) {
    foreach ($post->likes as $like) {
        echo $like->user->name; // No additional query
    }
}
```

### Load Liked Models with User

```php
$user = User::with('likes.likeable')->find(1);

foreach ($user->likes as $like) {
    $model = $like->likeable; // No additional query
    echo get_class($model);
}
```

### Load Like Counts

```php
$posts = Post::withCount('likes')->get();

foreach ($posts as $post) {
    echo $post->likes_count; // No additional query
}
```

## Custom Scopes

While not provided by default, you can add custom query scopes for common patterns:

### On the Likeable Model

```php
namespace App\Models;

use Akira\Likeable\Concerns\Likeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use Likeable;
    
    public function scopeMostLiked(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount('likes')
            ->orderByDesc('likes_count')
            ->limit($limit);
    }
    
    public function scopeLikedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('likes', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
```

Usage:

```php
$topPosts = Post::mostLiked(20)->get();
$userLikedPosts = Post::likedBy($user)->get();
```

## Relationship Constraints

Add constraints when loading relationships:

```php
// Only load likes from the last 30 days
$post = Post::with(['likes' => function ($query) {
    $query->where('created_at', '>', now()->subDays(30));
}])->find(1);

// Only load verified users who liked
$post = Post::with(['likes.user' => function ($query) {
    $query->where('verified', true);
}])->find(1);
```

## Polymorphic Relationship Details

The polymorphic setup uses two columns:

- `likeable_type`: Stores the full class name (e.g., `"App\Models\Post"`)
- `likeable_id`: Stores the model's primary key

Laravel automatically maps these when you call `morphTo()` and `morphMany()`.

### Custom Morph Map

To use shorter type names instead of full class names:

```php
// In AppServiceProvider
use Illuminate\Database\Eloquent\Relations\Relation;

public function boot()
{
    Relation::morphMap([
        'post' => \App\Models\Post::class,
        'comment' => \App\Models\Comment::class,
    ]);
}
```

Now `likeable_type` stores `"post"` instead of `"App\Models\Post"`.

**Previous:** [Events](04-events.md) | **Next:** [Configuration](06-configuration.md)
