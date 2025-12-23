# Events

Laravel Likeable dispatches events when likes are created or removed, allowing you to respond to like activity throughout your application.

## Available Events

### LikedEvent

Dispatched when a like is created.

**Namespace:** `Akira\Likeable\Events\LikedEvent`

**Properties:**
- `public Likeable $likeable` - The like record that was created

**When Dispatched:**
- When a user calls `like()` on a model
- When `toggleLike()` creates a new like

### UnLikedEvent

Dispatched when a like is removed.

**Namespace:** `Akira\Likeable\Events\UnLikedEvent`

**Properties:**
- `public Likeable $likeable` - The like record that was deleted

**When Dispatched:**
- When a user calls `unlike()` on a model
- When `toggleLike()` removes an existing like

## Event Structure

Both events use Laravel's `Dispatchable` trait and are automatically dispatched through the Likeable model's lifecycle:

```php
// LikedEvent
namespace Akira\Likeable\Events;

use Akira\Likeable\Likeable;
use Illuminate\Foundation\Events\Dispatchable;

final class LikedEvent
{
    use Dispatchable;
    
    public function __construct(public Likeable $likeable) {}
}
```

The event instances provide access to the complete like record, including relationships.

## Listening to Events

### Creating a Listener

Generate a listener using Artisan:

```bash
php artisan make:listener NotifyModelOwner
```

Implement the listener:

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use App\Notifications\ContentLikedNotification;

class NotifyModelOwner
{
    public function handle(LikedEvent $event): void
    {
        $like = $event->likeable;
        $likeable = $like->likeable; // The actual Post, Comment, etc.
        $liker = $like->user;
        
        // Notify the content owner
        if ($likeable->user_id !== $liker->id) {
            $likeable->user->notify(
                new ContentLikedNotification($liker, $likeable)
            );
        }
    }
}
```

### Registering the Listener

Register the listener in `EventServiceProvider`:

```php
use Akira\Likeable\Events\LikedEvent;
use App\Listeners\NotifyModelOwner;

protected $listen = [
    LikedEvent::class => [
        NotifyModelOwner::class,
    ],
];
```

Or use auto-discovery by following Laravel's listener conventions.

## Accessing Event Data

The `Likeable` model instance provides access to relationships:

```php
use Akira\Likeable\Events\LikedEvent;

class LogLikeActivity
{
    public function handle(LikedEvent $event): void
    {
        $like = $event->likeable;
        
        // Get the user who liked
        $user = $like->user;
        // or
        $user = $like->liker();
        
        // Get the liked model
        $model = $like->likeable;
        
        // Access attributes
        $likeableType = $like->likeable_type; // e.g., "App\Models\Post"
        $likeableId = $like->likeable_id;     // e.g., 123
        $userId = $like->user_id;             // e.g., 42
        
        Log::info("User {$user->name} liked {$likeableType}#{$likeableId}");
    }
}
```

## Practical Examples

### Send Notification

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use App\Models\Post;
use App\Notifications\PostLikedNotification;

class SendPostLikedNotification
{
    public function handle(LikedEvent $event): void
    {
        $like = $event->likeable;
        $post = $like->likeable;
        
        // Only notify for posts, not other likeable models
        if (!$post instanceof Post) {
            return;
        }
        
        // Don't notify users who like their own posts
        if ($post->user_id === $like->user_id) {
            return;
        }
        
        $post->user->notify(
            new PostLikedNotification($like->user, $post)
        );
    }
}
```

### Track Analytics

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use App\Models\Analytics;

class TrackLikeAnalytics
{
    public function handle(LikedEvent $event): void
    {
        $like = $event->likeable;
        
        Analytics::create([
            'event' => 'like_created',
            'user_id' => $like->user_id,
            'likeable_type' => $like->likeable_type,
            'likeable_id' => $like->likeable_id,
            'timestamp' => now(),
        ]);
    }
}
```

### Update Cache

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use Akira\Likeable\Events\UnLikedEvent;
use Illuminate\Support\Facades\Cache;

class UpdateLikeCountCache
{
    public function handleLiked(LikedEvent $event): void
    {
        $like = $event->likeable;
        $cacheKey = "likes:{$like->likeable_type}:{$like->likeable_id}";
        
        Cache::forget($cacheKey);
    }
    
    public function handleUnliked(UnLikedEvent $event): void
    {
        $like = $event->likeable;
        $cacheKey = "likes:{$like->likeable_type}:{$like->likeable_id}";
        
        Cache::forget($cacheKey);
    }
}
```

Register both methods:

```php
protected $listen = [
    LikedEvent::class => [
        'App\Listeners\UpdateLikeCountCache@handleLiked',
    ],
    UnLikedEvent::class => [
        'App\Listeners\UpdateLikeCountCache@handleUnliked',
    ],
];
```

### Award Points

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;

class AwardPointsForLike
{
    public function handle(LikedEvent $event): void
    {
        $like = $event->likeable;
        $contentOwner = $like->likeable->user;
        
        // Award points to content creator
        $contentOwner->increment('points', 5);
    }
}
```

### Queue Processing

For expensive operations, queue your listeners:

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessLikeNotification implements ShouldQueue
{
    public function handle(LikedEvent $event): void
    {
        // This runs asynchronously in the queue
        $like = $event->likeable;
        
        // Expensive operation
        $this->generateEmailDigest($like);
    }
}
```

### Conditional Logic

Handle different model types differently:

```php
namespace App\Listeners;

use Akira\Likeable\Events\LikedEvent;
use App\Models\Post;
use App\Models\Comment;

class HandleLikeByType
{
    public function handle(LikedEvent $event): void
    {
        $model = $event->likeable->likeable;
        
        match (true) {
            $model instanceof Post => $this->handlePostLike($model),
            $model instanceof Comment => $this->handleCommentLike($model),
            default => null,
        };
    }
    
    private function handlePostLike(Post $post): void
    {
        // Post-specific logic
    }
    
    private function handleCommentLike(Comment $comment): void
    {
        // Comment-specific logic
    }
}
```

## Testing with Events

In tests, you can fake events to assert they were dispatched:

```php
use Akira\Likeable\Events\LikedEvent;
use Illuminate\Support\Facades\Event;

test('liking a post dispatches event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    Event::assertDispatched(LikedEvent::class);
});
```

Or test specific event properties:

```php
Event::assertDispatched(function (LikedEvent $event) use ($post) {
    return $event->likeable->likeable_id === $post->id;
});
```

**Previous:** [Like Status](03-like-status.md) | **Next:** [Relationships](05-relationships.md)
