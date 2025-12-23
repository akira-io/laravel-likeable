# Testing

This guide covers testing strategies and best practices for applications using Laravel Likeable.

## Basic Test Setup

Configure your test environment to use the package:

```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Akira\Likeable\LikeableServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LikeableServiceProvider::class,
        ];
    }
    
    protected function getEnvironmentSetUp($app)
    {
        // Load migrations
        include_once __DIR__.'/../database/migrations/create_likeable_table.php.stub';
        (new \CreateLikeableTable)->up();
    }
}
```

## Feature Tests

### Testing Like Functionality

```php
use App\Models\User;
use App\Models\Post;

test('user can like a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $result = $user->like($post);
    
    expect($result)->toBeTrue()
        ->and($post->likesCount())->toBe(1)
        ->and($post->likers())->toHaveCount(1);
});

test('like creates database record', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    $this->assertDatabaseHas(config('likeable.table'), [
        'user_id' => $user->id,
        'likeable_id' => $post->id,
        'likeable_type' => Post::class,
    ]);
});
```

### Testing Unlike Functionality

```php
test('user can unlike a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    expect($post->likesCount())->toBe(1);
    
    $result = $user->unlike($post);
    
    expect($result)->toBeTrue()
        ->and($post->likesCount())->toBe(0);
});

test('unliking non-existent like returns false', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $result = $user->unlike($post);
    
    expect($result)->toBeFalse();
});
```

### Testing Toggle Functionality

```php
test('toggle like creates like when not liked', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->toggleLike($post);
    
    expect($post->likesCount())->toBe(1);
});

test('toggle like removes like when already liked', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    expect($post->likesCount())->toBe(1);
    
    $user->toggleLike($post);
    
    expect($post->likesCount())->toBe(0);
});
```

## Testing Like Status

```php
test('attach like status adds has_liked attribute', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    $user->attachLikeStatus($post);
    
    expect($post->has_liked)->toBeTrue();
});

test('attach like status works with collections', function () {
    $user = User::factory()->create();
    $posts = Post::factory()->count(3)->create();
    
    $user->like($posts[0]);
    $user->like($posts[2]);
    
    $user->attachLikeStatus($posts);
    
    expect($posts[0]->has_liked)->toBeTrue()
        ->and($posts[1]->has_liked)->toBeFalse()
        ->and($posts[2]->has_liked)->toBeTrue();
});

test('attach like status works with paginated results', function () {
    $user = User::factory()->create();
    Post::factory()->count(20)->create();
    
    $firstPost = Post::first();
    $user->like($firstPost);
    
    $posts = Post::paginate(10);
    $user->attachLikeStatus($posts);
    
    expect($posts->first()->has_liked)->toBeTrue();
});
```

## Testing Events

```php
use Akira\Likeable\Events\LikedEvent;
use Akira\Likeable\Events\UnLikedEvent;
use Illuminate\Support\Facades\Event;

test('liking dispatches event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    Event::assertDispatched(LikedEvent::class);
});

test('unliking dispatches event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    $user->unlike($post);
    
    Event::assertDispatched(UnLikedEvent::class);
});

test('event contains correct data', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    Event::assertDispatched(function (LikedEvent $event) use ($post, $user) {
        return $event->likeable->likeable_id === $post->id
            && $event->likeable->user_id === $user->id;
    });
});
```

## Testing Relationships

```php
test('likeable has likes relationship', function () {
    $post = Post::factory()->create();
    $users = User::factory()->count(3)->create();
    
    foreach ($users as $user) {
        $user->like($post);
    }
    
    expect($post->likes)->toHaveCount(3)
        ->and($post->likes->first())->toBeInstanceOf(Likeable::class);
});

test('user has likes relationship', function () {
    $user = User::factory()->create();
    $posts = Post::factory()->count(3)->create();
    
    foreach ($posts as $post) {
        $user->like($post);
    }
    
    expect($user->likes)->toHaveCount(3);
});

test('likers returns user collection', function () {
    $post = Post::factory()->create();
    $users = User::factory()->count(3)->create();
    
    foreach ($users as $user) {
        $user->like($post);
    }
    
    $likers = $post->likers();
    
    expect($likers)->toHaveCount(3)
        ->and($likers->first())->toBeInstanceOf(User::class);
});
```

## Testing Configuration

```php
test('uses custom table name', function () {
    config(['likeable.table' => 'custom_likes']);
    
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    $this->assertDatabaseHas('custom_likes', [
        'user_id' => $user->id,
    ]);
});

test('uses custom user foreign key', function () {
    config(['likeable.user_foreign_key' => 'account_id']);
    
    // Test would require migration adjustment
    // This is an example of testing configuration
});
```

## Testing with Factories

Create factories for testing:

```php
namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;
    
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'user_id' => User::factory(),
        ];
    }
    
    public function withLikes(int $count = 3)
    {
        return $this->afterCreating(function (Post $post) use ($count) {
            $users = User::factory()->count($count)->create();
            
            foreach ($users as $user) {
                $user->like($post);
            }
        });
    }
}
```

Usage:

```php
test('post with likes factory', function () {
    $post = Post::factory()->withLikes(5)->create();
    
    expect($post->likesCount())->toBe(5);
});
```

## HTTP Tests

Test like endpoints:

```php
test('authenticated user can like post via endpoint', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $this->actingAs($user)
        ->post("/posts/{$post->id}/like")
        ->assertRedirect();
    
    expect($post->likesCount())->toBe(1);
});

test('guest cannot like post', function () {
    $post = Post::factory()->create();
    
    $this->post("/posts/{$post->id}/like")
        ->assertRedirect(route('login'));
});

test('like endpoint returns json for api', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson("/api/posts/{$post->id}/like");
    
    $response->assertOk()
        ->assertJson([
            'liked' => true,
            'likes_count' => 1,
        ]);
});
```

## Database Testing

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('like count is accurate', function () {
    $post = Post::factory()->create();
    $users = User::factory()->count(10)->create();
    
    foreach ($users as $user) {
        $user->like($post);
    }
    
    expect($post->likesCount())->toBe(10)
        ->and($post->likes()->count())->toBe(10);
});

test('duplicate likes are prevented with unique constraint', function () {
    // Requires unique constraint migration
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $user->like($post);
    
    $this->expectException(\Illuminate\Database\QueryException::class);
    $user->like($post);
});
```

## Performance Testing

```php
test('attach like status is efficient', function () {
    $user = User::factory()->create();
    $posts = Post::factory()->count(100)->create();
    
    // Like half of them
    $posts->take(50)->each(fn($post) => $user->like($post));
    
    // Count queries
    DB::enableQueryLog();
    $user->attachLikeStatus($posts);
    $queries = DB::getQueryLog();
    
    // Should be 1 query (to fetch likes) + any eager loading
    expect(count($queries))->toBeLessThanOrEqual(2);
});
```

## Mock Testing

Mock the Likeable model:

```php
use Akira\Likeable\Likeable;
use Mockery;

test('service uses like model correctly', function () {
    $mock = Mockery::mock(Likeable::class);
    $mock->shouldReceive('create')->once()->andReturn(true);
    
    $this->app->instance(Likeable::class, $mock);
    
    // Test code using the mocked instance
});
```

## Test Helpers

Create reusable test helpers:

```php
namespace Tests;

use App\Models\User;
use App\Models\Post;

trait LikeHelpers
{
    protected function createLikedPost(User $user = null): Post
    {
        $user ??= User::factory()->create();
        $post = Post::factory()->create();
        $user->like($post);
        
        return $post;
    }
    
    protected function assertLiked(User $user, $model): void
    {
        $this->assertTrue(
            $user->likes()
                ->where('likeable_id', $model->id)
                ->where('likeable_type', get_class($model))
                ->exists(),
            'User has not liked the model'
        );
    }
    
    protected function assertNotLiked(User $user, $model): void
    {
        $this->assertFalse(
            $user->likes()
                ->where('likeable_id', $model->id)
                ->where('likeable_type', get_class($model))
                ->exists(),
            'User has liked the model'
        );
    }
}
```

Use in tests:

```php
use Tests\LikeHelpers;

uses(LikeHelpers::class);

test('helper creates liked post', function () {
    $user = User::factory()->create();
    $post = $this->createLikedPost($user);
    
    $this->assertLiked($user, $post);
});
```

**Previous:** [Advanced Usage](07-advanced-usage.md) | **Next:** [Troubleshooting](09-troubleshooting.md)
