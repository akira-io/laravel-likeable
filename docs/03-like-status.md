# Like Status

When displaying lists of content, you often need to know whether the authenticated user has liked each item. Laravel Likeable provides the `attachLikeStatus()` method to efficiently attach this information.

## The Problem

Consider this inefficient approach:

```php
// DON'T DO THIS - N+1 query problem
$posts = Post::all();

foreach ($posts as $post) {
    // This queries the database for EACH post
    $post->hasLiked = $user->likes()
        ->where('likeable_id', $post->id)
        ->where('likeable_type', Post::class)
        ->exists();
}
```

This creates a database query for every item in the collection, leading to poor performance.

## The Solution: attachLikeStatus()

The `attachLikeStatus()` method solves this by fetching all like statuses in a single query and attaching them to your models:

```php
$user = auth()->user();
$posts = Post::all();

$user->attachLikeStatus($posts);

// Now each post has a 'has_liked' attribute
foreach ($posts as $post) {
    echo $post->has_liked; // true or false
}
```

Only **one** additional query is executed, regardless of collection size.

## Supported Data Structures

The method works with various Laravel data structures:

### Single Model

```php
$post = Post::find(1);
$user->attachLikeStatus($post);

if ($post->has_liked) {
    echo "You liked this post";
}
```

### Collection

```php
$posts = Post::where('published', true)->get();
$user->attachLikeStatus($posts);
```

### Lazy Collection

```php
$posts = Post::cursor(); // LazyCollection
$user->attachLikeStatus($posts);
```

### Paginator

```php
$posts = Post::paginate(15);
$user->attachLikeStatus($posts);

// Works in Blade
@foreach ($posts as $post)
    @if ($post->has_liked)
        <span>❤️ Liked</span>
    @endif
@endforeach

{{ $posts->links() }}
```

### Cursor Paginator

```php
$posts = Post::cursorPaginate(15);
$user->attachLikeStatus($posts);
```

### Simple Paginator

```php
$posts = Post::simplePaginate(15);
$user->attachLikeStatus($posts);
```

## The has_liked Attribute

After calling `attachLikeStatus()`, each model receives a `has_liked` attribute:

```php
$user->attachLikeStatus($posts);

foreach ($posts as $post) {
    var_dump($post->has_liked); // bool: true or false
}
```

- Returns `true` if the user has liked the model
- Returns `false` if the user has not liked the model
- Only added to models using the `Likeable` trait

## Custom Resolvers

If you're working with nested data or non-standard structures, use a resolver closure:

```php
// Posts wrapped in DTOs or other structures
$items = [
    ['post' => $post1, 'meta' => [...]],
    ['post' => $post2, 'meta' => [...]],
];

$user->attachLikeStatus($items, fn($item) => $item['post']);

// Now each post inside the structure has has_liked
foreach ($items as $item) {
    echo $item['post']->has_liked;
}
```

The resolver extracts the actual model from each item before attaching the like status.

## Practical Examples

### API Resource

```php
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'likes_count' => $this->likesCount(),
            'has_liked' => $this->has_liked ?? false,
        ];
    }
}

// In controller
$posts = Post::paginate(20);
$user->attachLikeStatus($posts);

return PostResource::collection($posts);
```

### Blade Component

```php
// In your component class
class PostList extends Component
{
    public Collection $posts;
    
    public function mount()
    {
        $this->posts = Post::latest()->take(10)->get();
        
        if (auth()->check()) {
            auth()->user()->attachLikeStatus($this->posts);
        }
    }
    
    public function render()
    {
        return view('components.post-list');
    }
}
```

```blade
{{-- In your component view --}}
@foreach ($posts as $post)
    <article>
        <h2>{{ $post->title }}</h2>
        <div class="likes">
            <span>{{ $post->likesCount() }} likes</span>
            @if ($post->has_liked ?? false)
                <button class="liked">Unlike</button>
            @else
                <button>Like</button>
            @endif
        </div>
    </article>
@endforeach
```

### Livewire Component

```php
class PostFeed extends Component
{
    public $posts;
    
    public function mount()
    {
        $this->loadPosts();
    }
    
    public function loadPosts()
    {
        $this->posts = Post::latest()->paginate(10);
        
        if (auth()->check()) {
            auth()->user()->attachLikeStatus($this->posts);
        }
    }
    
    public function render()
    {
        return view('livewire.post-feed');
    }
}
```

## Performance Considerations

The `attachLikeStatus()` method executes a single query to fetch all relevant likes:

```sql
SELECT * FROM likeables 
WHERE user_id = ? 
  AND likeable_type IN (...)
  AND likeable_id IN (...)
```

This is far more efficient than individual queries per item. For large collections (1000+ items), consider pagination to keep query response times optimal.

## Mixed Model Types

The method intelligently handles collections containing different model types:

```php
$items = collect([
    Post::find(1),
    Comment::find(5),
    Post::find(2),
]);

$user->attachLikeStatus($items);

// Each item has has_liked, regardless of type
```

Only models using the `Likeable` trait receive the `has_liked` attribute; other models in the collection are ignored.

**Previous:** [Basic Usage](02-basic-usage.md) | **Next:** [Events](04-events.md)
