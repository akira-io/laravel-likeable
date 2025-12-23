# Advanced Usage

This guide covers advanced patterns and techniques for working with Laravel Likeable in complex scenarios.

## Custom Model Implementation

Extend the base Likeable model to add custom functionality:

```php
namespace App\Models;

use Akira\Likeable\Likeable as BaseLikeable;

class Like extends BaseLikeable
{
    protected $appends = ['is_recent'];
    
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->isAfter(now()->subHours(24));
    }
    
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>', now()->subDay());
    }
    
    public function isPremium(): bool
    {
        return $this->user->subscription !== null;
    }
}
```

Update configuration:

```php
// config/likeable.php
'model' => \App\Models\Like::class,
```

## Query Scopes

The Likeable model includes a `withType` scope for filtering by model type:

```php
use Akira\Likeable\Likeable;
use App\Models\Post;

// Get all likes for Post models
$postLikes = Likeable::withType(Post::class)->get();

// Combine with other constraints
$recentPostLikes = Likeable::withType(Post::class)
    ->where('created_at', '>', now()->subWeek())
    ->get();
```

The scope resolves the model's morph class automatically, handling custom morph maps.

## Batch Operations

When processing multiple likes, optimize database queries:

```php
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

$user = User::find(1);
$postIds = [1, 2, 3, 4, 5];

DB::transaction(function () use ($user, $postIds) {
    foreach ($postIds as $postId) {
        $post = Post::find($postId);
        $user->like($post);
    }
});
```

For bulk inserts without events:

```php
$likesData = collect($postIds)->map(fn($id) => [
    'user_id' => $user->id,
    'likeable_type' => Post::class,
    'likeable_id' => $id,
    'created_at' => now(),
    'updated_at' => now(),
]);

DB::table(config('likeable.table'))->insert($likesData->toArray());
```

Note: This bypasses events and model boot logic.

## Conditional Likes

Implement business logic before allowing likes:

```php
use App\Models\Post;

class LikeService
{
    public function likePost(User $user, Post $post): bool
    {
        // Check if post allows likes
        if (!$post->allows_likes) {
            throw new \Exception('This post does not allow likes');
        }
        
        // Check user permissions
        if ($user->isBanned()) {
            throw new \Exception('Banned users cannot like content');
        }
        
        // Prevent duplicate likes (optional, handled by unique constraint)
        if ($this->hasLiked($user, $post)) {
            return false;
        }
        
        return $user->like($post);
    }
    
    private function hasLiked(User $user, Post $post): bool
    {
        return $user->likes()
            ->where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->exists();
    }
}
```

## Preventing Duplicate Likes

Add a unique constraint to prevent duplicate likes:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('likeable.table'), function (Blueprint $table) {
            $table->unique([
                config('likeable.user_foreign_key'),
                'likeable_id',
                'likeable_type'
            ], 'unique_like');
        });
    }
};
```

Handle constraint violations gracefully:

```php
use Illuminate\Database\QueryException;

try {
    $user->like($post);
} catch (QueryException $e) {
    if ($e->getCode() === '23000') { // Duplicate entry
        // Already liked
        return false;
    }
    throw $e;
}
```

## Aggregation and Statistics

Calculate like statistics across your application:

```php
use Akira\Likeable\Likeable;
use App\Models\Post;

// Most active likers
$topLikers = Likeable::select('user_id')
    ->selectRaw('COUNT(*) as like_count')
    ->groupBy('user_id')
    ->orderByDesc('like_count')
    ->limit(10)
    ->with('user')
    ->get();

// Most liked content
$mostLikedPosts = Post::withCount('likes')
    ->orderByDesc('likes_count')
    ->take(10)
    ->get();

// Like trends over time
$likesPerDay = Likeable::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->where('created_at', '>', now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

## Working with Multiple Models

Attach like status to mixed collections:

```php
$user = auth()->user();

$posts = Post::latest()->take(5)->get();
$comments = Comment::latest()->take(5)->get();

$items = collect($posts)->merge($comments);

$user->attachLikeStatus($items);

foreach ($items as $item) {
    echo get_class($item) . ": " . ($item->has_liked ? "Liked" : "Not liked");
}
```

## Rate Limiting

Prevent like spam with rate limiting:

```php
use Illuminate\Support\Facades\RateLimiter;

class LikeController extends Controller
{
    public function store(Post $post)
    {
        $key = 'like:' . auth()->id();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many likes. Try again in {$seconds} seconds."
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 10 likes per minute
        
        auth()->user()->like($post);
        
        return response()->json(['success' => true]);
    }
}
```

Or use middleware:

```php
Route::post('/posts/{post}/like', [LikeController::class, 'store'])
    ->middleware('throttle:likes');
```

Define in `RouteServiceProvider`:

```php
RateLimiter::for('likes', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

## Caching Like Counts

Cache like counts for performance:

```php
use Illuminate\Support\Facades\Cache;

class Post extends Model
{
    use Likeable;
    
    public function cachedLikesCount(): int
    {
        return Cache::remember(
            "post:{$this->id}:likes_count",
            now()->addHours(1),
            fn() => $this->likes()->count()
        );
    }
}
```

Invalidate cache when likes change:

```php
use Akira\Likeable\Events\LikedEvent;
use Akira\Likeable\Events\UnLikedEvent;

class InvalidateLikeCacheListener
{
    public function handle(LikedEvent|UnLikedEvent $event): void
    {
        $likeable = $event->likeable->likeable;
        $cacheKey = "post:{$likeable->id}:likes_count";
        
        Cache::forget($cacheKey);
    }
}
```

## Soft Deletes Integration

Handle soft-deleted models:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use Likeable, SoftDeletes;
}

// Include trashed models in like counts
$post = Post::withTrashed()->find(1);
$count = $post->likes()->count();

// Only count likes on non-trashed posts
$activeLikes = $post->likes()
    ->whereHas('likeable', function ($query) {
        $query->whereNull('deleted_at');
    })
    ->count();
```

## Authorization Policies

Implement policies for like actions:

```php
namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function like(User $user, Post $post): bool
    {
        // Users can't like their own posts
        if ($post->user_id === $user->id) {
            return false;
        }
        
        // Post must be published
        if (!$post->isPublished()) {
            return false;
        }
        
        return true;
    }
}
```

Use in controllers:

```php
public function like(Post $post)
{
    $this->authorize('like', $post);
    
    auth()->user()->like($post);
    
    return back();
}
```

## API Resources

Transform like data for APIs:

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'likes' => [
                'count' => $this->likesCount(),
                'has_liked' => $this->has_liked ?? false,
                'recent_likers' => UserResource::collection(
                    $this->likes()->latest()->take(3)->get()->pluck('user')
                ),
            ],
        ];
    }
}
```

## Testing Helpers

Create test helpers for likes:

```php
namespace Tests;

trait WithLikes
{
    protected function createLike(User $user, $model)
    {
        return $user->like($model);
    }
    
    protected function assertModelLiked($model, User $user)
    {
        $this->assertTrue(
            $user->likes()
                ->where('likeable_id', $model->id)
                ->where('likeable_type', get_class($model))
                ->exists()
        );
    }
    
    protected function assertModelNotLiked($model, User $user)
    {
        $this->assertFalse(
            $user->likes()
                ->where('likeable_id', $model->id)
                ->where('likeable_type', get_class($model))
                ->exists()
        );
    }
}
```

Use in tests:

```php
use Tests\WithLikes;

test('user can like post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $this->createLike($user, $post);
    
    $this->assertModelLiked($post, $user);
});
```

## Database Transactions

Ensure data consistency with transactions:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($user, $post) {
    $user->like($post);
    
    // Update user statistics
    $user->increment('total_likes_given');
    
    // Update post statistics
    $post->increment('engagement_score', 5);
});
```

**Previous:** [Configuration](06-configuration.md) | **Next:** [Testing](08-testing.md)
