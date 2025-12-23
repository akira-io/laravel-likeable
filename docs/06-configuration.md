# Configuration

Laravel Likeable can be customized through the `config/likeable.php` configuration file. This guide explains each option and how to use them effectively.

## Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="likeable-config"
```

This creates `config/likeable.php` in your application.

## Configuration Options

### uuids

**Type:** `boolean`  
**Default:** `false`

Controls whether UUIDs are used as primary keys for like records instead of auto-incrementing integers.

```php
'uuids' => false,
```

When enabled, the package automatically generates ordered UUIDs using `Str::orderedUuid()` when creating new like records.

**Example:**

```php
'uuids' => true,
```

After enabling, update your migration:

```php
Schema::create(config('likeable.table'), function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ... rest of schema
});
```

**Use Cases:**
- Distributed systems where auto-incrementing IDs create conflicts
- When you need non-sequential, globally unique identifiers
- Database sharding or multi-tenant architectures

### user_foreign_key

**Type:** `string`  
**Default:** `'user_id'`

Specifies the foreign key column name that references the user model.

```php
'user_foreign_key' => 'user_id',
```

Change this if your user table uses a different primary key column name.

**Example:**

```php
'user_foreign_key' => 'account_id',
```

Update your migration accordingly:

```php
Schema::create(config('likeable.table'), function (Blueprint $table) {
    $table->id();
    $table->foreignId('account_id')->index();
    $table->morphs('likeable');
    $table->timestamps();
});
```

**Use Cases:**
- Legacy databases with non-standard column names
- Multi-tenant applications with custom user table structures
- Integration with existing authentication systems

### table

**Type:** `string`  
**Default:** `'likeables'`

Defines the database table name for storing like records.

```php
'table' => 'likeables',
```

Customize the table name to match your naming conventions.

**Example:**

```php
'table' => 'user_likes',
```

The migration automatically uses this value:

```php
Schema::create(config('likeable.table'), function (Blueprint $table) {
    // Table schema
});
```

**Use Cases:**
- Adhering to specific database naming conventions
- Avoiding table name conflicts in shared databases
- Organizational preferences for table naming

### model

**Type:** `string`  
**Default:** `\Akira\Likeable\Likeable::class`

Specifies the fully qualified class name for the Likeable model.

```php
'model' => \Akira\Likeable\Likeable::class,
```

Replace with a custom model class to extend or override default behavior.

**Example:**

```php
'model' => \App\Models\CustomLikeable::class,
```

Create your custom model:

```php
namespace App\Models;

use Akira\Likeable\Likeable as BaseLikeable;

class CustomLikeable extends BaseLikeable
{
    // Add custom methods or override behavior
    
    public function isPremiumLike(): bool
    {
        return $this->user->isPremium();
    }
}
```

**Use Cases:**
- Adding custom methods to the like model
- Implementing additional business logic
- Extending the model with extra attributes or relationships

## Configuration in Practice

### Accessing Configuration

The package internally accesses configuration using Laravel's `config()` helper:

```php
$tableName = config('likeable.table');
$modelClass = config('likeable.model');
$userKey = config('likeable.user_foreign_key');
```

### Runtime Configuration

While not recommended, you can modify configuration at runtime:

```php
config(['likeable.table' => 'custom_likes']);
```

This only affects the current request and should be used sparingly.

### Environment-Specific Configuration

Use environment variables for different configurations:

```php
// .env
LIKEABLE_TABLE=user_likes
LIKEABLE_USE_UUIDS=true
```

```php
// config/likeable.php
return [
    'uuids' => env('LIKEABLE_USE_UUIDS', false),
    'table' => env('LIKEABLE_TABLE', 'likeables'),
    // ...
];
```

## Advanced Configurations

### Multiple Like Types

For applications needing separate like tables per model type:

```php
// config/likeable.php
return [
    'tables' => [
        'posts' => 'post_likes',
        'comments' => 'comment_likes',
    ],
];
```

This requires extending the package with custom logic, as it's not supported out of the box.

### Custom Primary Keys

If your users table uses a non-standard primary key:

```php
// User model
class User extends Authenticatable
{
    use Liker;
    
    protected $primaryKey = 'user_uuid';
    public $incrementing = false;
    protected $keyType = 'string';
}
```

Ensure `user_foreign_key` matches:

```php
'user_foreign_key' => 'user_uuid',
```

### Database Indexes

The default migration includes indexes on important columns. For high-traffic applications, consider additional indexes:

```php
Schema::create(config('likeable.table'), function (Blueprint $table) {
    $table->id();
    $table->foreignId(config('likeable.user_foreign_key'))->index();
    $table->morphs('likeable');
    $table->timestamps();
    
    // Additional composite index
    $table->index(['likeable_type', 'likeable_id', 'user_id'], 'likeable_user_index');
});
```

This speeds up queries checking if a user has liked a specific model.

## Caching Configuration

Laravel caches configuration in production. After changing config files:

```bash
php artisan config:cache
```

Clear the cache when needed:

```bash
php artisan config:clear
```

## Testing Configuration

Override configuration in tests:

```php
test('uses custom table name', function () {
    config(['likeable.table' => 'test_likes']);
    
    // Test code
});
```

Or set defaults in `TestCase`:

```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config(['likeable.uuids' => true]);
    }
}
```

## Configuration Validation

Validate configuration values at runtime:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $model = config('likeable.model');
        
        if (!class_exists($model)) {
            throw new \Exception("Likeable model class does not exist: {$model}");
        }
    }
}
```

## Default Configuration Reference

Complete default configuration:

```php
<?php

use Akira\Likeable\Likeable;

return [
    'uuids' => false,
    'user_foreign_key' => 'user_id',
    'table' => 'likeables',
    'model' => Likeable::class,
];
```

**Previous:** [Relationships](05-relationships.md) | **Next:** [Advanced Usage](07-advanced-usage.md)
