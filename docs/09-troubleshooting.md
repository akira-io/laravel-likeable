# Troubleshooting

Common issues and solutions when working with Laravel Likeable.

## Installation Issues

### Migration Not Found

**Problem:** Migration file is not found after publishing.

**Solution:**
```bash
# Clear config cache
php artisan config:clear

# Republish migrations
php artisan vendor:publish --tag="likeable-migrations" --force

# Run migrations
php artisan migrate
```

### Service Provider Not Registered

**Problem:** Package functionality not available.

**Solution:**

The package uses Laravel's auto-discovery. If it's not working:

```php
// config/app.php
'providers' => [
    // ...
    Akira\Likeable\LikeableServiceProvider::class,
],
```

Then clear cache:

```bash
php artisan config:clear
php artisan cache:clear
```

## Database Issues

### Table Already Exists

**Problem:** `SQLSTATE[42S01]: Base table or view already exists`

**Solution:**

Check if table already exists:

```bash
php artisan db:show --table=likeables
```

Either drop the existing table or rename the new one via config:

```php
// config/likeable.php
'table' => 'user_likes',
```

### Foreign Key Constraint Fails

**Problem:** `SQLSTATE[23000]: Integrity constraint violation`

**Solution:**

Ensure the user exists:

```php
$user = User::find($userId);
if (!$user) {
    throw new \Exception('User not found');
}

$user->like($post);
```

Or check `user_id` is set correctly:

```php
// In migration
$table->foreignId(config('likeable.user_foreign_key'))
    ->constrained('users')
    ->onDelete('cascade');
```

### Duplicate Entry Error

**Problem:** `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`

**Solution:**

Add unique constraint to prevent duplicates:

```php
// Create migration
php artisan make:migration add_unique_constraint_to_likeable_table
```

```php
public function up()
{
    Schema::table(config('likeable.table'), function (Blueprint $table) {
        $table->unique([
            config('likeable.user_foreign_key'),
            'likeable_id',
            'likeable_type'
        ], 'unique_user_like');
    });
}
```

Handle in code:

```php
try {
    $user->like($post);
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23000') {
        // Already liked
        return response()->json(['message' => 'Already liked'], 409);
    }
    throw $e;
}
```

## Configuration Issues

### Wrong Table Name

**Problem:** Queries fail because table name doesn't match configuration.

**Solution:**

Ensure config is published and cached:

```bash
php artisan vendor:publish --tag="likeable-config"
php artisan config:cache
```

Verify table name in config:

```php
// config/likeable.php
'table' => 'likeables', // Must match migration
```

### Custom User Model Not Working

**Problem:** Relationships fail with custom user model.

**Solution:**

Ensure auth config points to your user model:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\CustomUser::class,
    ],
],
```

The package uses `config('auth.providers.users.model')` automatically.

## Relationship Issues

### Likes Not Loading

**Problem:** `$post->likes` returns empty collection.

**Solution:**

Check the trait is added:

```php
use Akira\Likeable\Concerns\Likeable;

class Post extends Model
{
    use Likeable; // Required
}
```

Verify likes exist:

```php
$count = DB::table(config('likeable.table'))
    ->where('likeable_id', $post->id)
    ->where('likeable_type', Post::class)
    ->count();

dd($count); // Should be > 0
```

### Likers Returns Empty

**Problem:** `$post->likers()` returns empty collection despite likes existing.

**Solution:**

The `likers()` method eager loads the user relationship. Check that users still exist:

```php
$likes = $post->likes()->with('user')->get();

foreach ($likes as $like) {
    if (!$like->user) {
        echo "User {$like->user_id} not found";
    }
}
```

Add cascade delete to clean up orphaned likes:

```php
// In migration
$table->foreignId(config('likeable.user_foreign_key'))
    ->constrained('users')
    ->onDelete('cascade');
```

## Event Issues

### Events Not Firing

**Problem:** `LikedEvent` or `UnLikedEvent` not dispatched.

**Solution:**

The events are dispatched through Eloquent's lifecycle. Verify model dispatches events:

```php
use Akira\Likeable\Likeable;

$likeable = Likeable::first();
dd($likeable->getDispatchesEvents());
// Should show: ['created' => LikedEvent::class, 'deleted' => UnLikedEvent::class]
```

Ensure you're not using bulk inserts which bypass model events:

```php
// This bypasses events:
DB::table('likeables')->insert([...]);

// This fires events:
$user->like($post);
```

### Event Listeners Not Called

**Problem:** Registered listeners not executing.

**Solution:**

Verify listener registration:

```php
// app/Providers/EventServiceProvider.php
use Akira\Likeable\Events\LikedEvent;
use App\Listeners\NotifyOnLike;

protected $listen = [
    LikedEvent::class => [
        NotifyOnLike::class,
    ],
];
```

Clear event cache:

```bash
php artisan event:clear
php artisan cache:clear
```

Check listener implementation:

```php
class NotifyOnLike
{
    public function handle(LikedEvent $event): void
    {
        logger('Like event fired', ['like_id' => $event->likeable->id]);
        // Your logic
    }
}
```

## Query Performance Issues

### N+1 Query Problem

**Problem:** Too many queries when displaying likes.

**Solution:**

Use eager loading:

```php
// Bad: N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->likesCount(); // New query each iteration
}

// Good: Eager load counts
$posts = Post::withCount('likes')->get();
foreach ($posts as $post) {
    echo $post->likes_count; // No additional queries
}
```

For like status:

```php
// Bad: Queries for each post
foreach ($posts as $post) {
    $post->has_liked = $user->likes()
        ->where('likeable_id', $post->id)
        ->exists();
}

// Good: Single query
$user->attachLikeStatus($posts);
```

### Slow Like Counts

**Problem:** `likesCount()` is slow on large datasets.

**Solution:**

Add database indexes:

```php
Schema::table(config('likeable.table'), function (Blueprint $table) {
    $table->index(['likeable_type', 'likeable_id']);
});
```

Cache counts:

```php
public function likesCount(): int
{
    return Cache::remember(
        "post:{$this->id}:likes",
        3600,
        fn() => $this->likes()->count()
    );
}
```

## Trait Issues

### Method Not Found

**Problem:** `Call to undefined method like()`

**Solution:**

Add the `Liker` trait to your User model:

```php
use Akira\Likeable\Concerns\Liker;

class User extends Authenticatable
{
    use Liker; // Required for like(), unlike(), toggleLike()
}
```

### Trait Conflicts

**Problem:** Method name conflicts with another trait.

**Solution:**

Use trait aliases:

```php
use Akira\Likeable\Concerns\Liker;
use SomeOtherTrait;

class User extends Authenticatable
{
    use Liker {
        like as likeModel;
    }
    use SomeOtherTrait;
    
    // Now use $user->likeModel($post)
}
```

## UUID Issues

### UUID Not Generated

**Problem:** UUIDs not being created when enabled.

**Solution:**

Ensure config is correct:

```php
// config/likeable.php
'uuids' => true,
```

Update migration to use UUID:

```php
Schema::create(config('likeable.table'), function (Blueprint $table) {
    $table->uuid('id')->primary(); // Not ->id()
    // ...
});
```

Clear config cache:

```bash
php artisan config:cache
```

### UUID Type Mismatch

**Problem:** Foreign key type mismatch with UUID.

**Solution:**

Ensure consistent key types:

```php
// If using UUIDs in users table
Schema::table('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
});

// Match in likeable table
Schema::create(config('likeable.table'), function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->index(); // Match user table type
    $table->morphs('likeable');
    $table->timestamps();
});
```

## Testing Issues

### Tests Failing After Installation

**Problem:** Tests fail with table not found errors.

**Solution:**

Run migrations in tests:

```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    $this->artisan('migrate');
    // Or
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
}
```

Or use `RefreshDatabase`:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class LikeTest extends TestCase
{
    use RefreshDatabase;
}
```

## Debug Techniques

### Enable Query Logging

```php
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

$user->like($post);

dd(DB::getQueryLog());
```

### Check Like Records

```php
$likes = DB::table(config('likeable.table'))
    ->where('user_id', $user->id)
    ->get();

dd($likes);
```

### Verify Configuration

```php
dd([
    'table' => config('likeable.table'),
    'model' => config('likeable.model'),
    'user_key' => config('likeable.user_foreign_key'),
    'uuids' => config('likeable.uuids'),
]);
```

### Check Relationships

```php
$post = Post::find(1);

dd([
    'has_trait' => in_array(Likeable::class, class_uses_recursive($post)),
    'likes_count' => $post->likes()->count(),
    'likers_count' => $post->likers()->count(),
]);
```

## Getting Help

If you continue experiencing issues:

1. Check the [GitHub issues](https://github.com/akira-io/laravel-likeable/issues)
2. Review your Laravel log files: `storage/logs/laravel.log`
3. Enable debug mode: `APP_DEBUG=true` in `.env`
4. Verify all dependencies are up to date: `composer update`

**Previous:** [Testing](08-testing.md)
