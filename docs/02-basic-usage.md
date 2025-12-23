# Basic Usage

This guide covers the fundamental operations for liking and unliking content using Laravel Likeable.

## Liking a Model

Use the `like()` method on any model that uses the `Liker` trait:

```php
use App\Models\User;
use App\Models\Post;

$user = User::find(1);
$post = Post::find(1);

$user->like($post);
```

The method returns `true` on success. When a like is created, a `LikedEvent` is automatically dispatched, allowing you to react to like actions throughout your application.

### Authenticated User Context

The package automatically uses the authenticated user when saving a like. If you're working within a request context:

```php
$post = Post::find(1);
auth()->user()->like($post);
```

The `user_id` is automatically set from the authenticated session.

## Unliking a Model

Remove a like using the `unlike()` method:

```php
$user = User::find(1);
$post = Post::find(1);

$user->unlike($post);
```

The method returns `true` if the like was successfully removed, or `false` if the like didn't exist. An `UnLikedEvent` is dispatched when a like is removed.

## Toggling Likes

Toggle the like status with a single method call:

```php
$user = User::find(1);
$post = Post::find(1);

// If not liked: creates a like
// If already liked: removes the like
$user->toggleLike($post);
```

This is particularly useful for UI implementations where a single button toggles between liked and unliked states. The method returns `true` when a like is added, and `true` when removed (since the delete operation succeeds).

## Checking Like Counts

Models using the `Likeable` trait can report their total like count:

```php
$post = Post::find(1);

$count = $post->likesCount();
// Returns: int (e.g., 42)
```

This method executes a fresh count query each time it's called.

## Retrieving Likers

Get all users who have liked a model:

```php
$post = Post::find(1);

$likers = $post->likers();
// Returns: Illuminate\Support\Collection of User models
```

The collection contains User model instances (or whatever model uses the `Liker` trait). Each user in the collection has liked the post.

### Working with the Likers Collection

```php
$likers = $post->likers();

// Get count
$likerCount = $likers->count();

// Get first liker
$firstLiker = $likers->first();

// Map to names
$likerNames = $likers->pluck('name');

// Filter likers
$verifiedLikers = $likers->filter(fn($user) => $user->isVerified());
```

## Accessing Raw Like Records

Access the underlying like records through the `likes()` relationship:

```php
$post = Post::find(1);

// Get all like records
$likes = $post->likes;

// Query the relationship
$recentLikes = $post->likes()
    ->where('created_at', '>', now()->subDays(7))
    ->get();

// Count without loading
$count = $post->likes()->count();
```

The `likes()` method returns a `MorphMany` relationship to the `Likeable` model.

## Retrieving User's Likes

Get all likes performed by a user:

```php
$user = User::find(1);

$userLikes = $user->likes;
// Returns: Collection of Likeable records

// Access the liked models
foreach ($user->likes as $like) {
    $likedModel = $like->likeable; // The actual Post, Comment, etc.
}
```

The `likes()` method on a `Liker` returns a `HasMany` relationship.

## Practical Examples

### Like Button Implementation

```php
// In your controller
public function toggleLike(Post $post)
{
    $user = auth()->user();
    $user->toggleLike($post);
    
    return back()->with('success', 'Like status updated');
}
```

### Display Like Count and Button

```php
// In your Blade view
<div class="post-likes">
    <span>{{ $post->likesCount() }} likes</span>
    
    <form action="{{ route('posts.like', $post) }}" method="POST">
        @csrf
        <button type="submit">Like</button>
    </form>
</div>
```

### List Top Likers

```php
$post = Post::find(1);
$topLikers = $post->likers()->take(5);

foreach ($topLikers as $liker) {
    echo $liker->name;
}
```

**Previous:** [Installation](01-installation.md) | **Next:** [Like Status](03-like-status.md)
