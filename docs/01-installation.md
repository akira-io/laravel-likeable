# Installation

Laravel Likeable is a lightweight package that adds like and unlike functionality to your Eloquent models. This guide will walk you through the installation and initial setup process.

## Requirements

- PHP 8.4 or higher
- Laravel 12.0 or higher
- A configured database connection

## Installing the Package

Install Laravel Likeable via Composer:

```bash
composer require akira/laravel-likeable
```

The package will automatically register its service provider through Laravel's package auto-discovery.

## Publishing Assets

### Publish the Migration

Publish the migration file to create the likes table:

```bash
php artisan vendor:publish --tag="likeable-migrations"
```

This creates a migration file in your `database/migrations` directory. Run the migration:

```bash
php artisan migrate
```

The migration creates a `likeables` table (configurable) with the following structure:
- `id` - Primary key
- `user_id` - Foreign key to the user who liked
- `likeable_id` - ID of the liked model
- `likeable_type` - Class name of the liked model (polymorphic)
- `timestamps` - Created and updated timestamps

### Publish the Configuration File

Optionally publish the configuration file to customize package behavior:

```bash
php artisan vendor:publish --tag="likeable-config"
```

This creates `config/likeable.php` in your application.

## Configuration

After publishing, you can customize the package through `config/likeable.php`:

```php
return [
    'uuids' => false,
    'user_foreign_key' => 'user_id',
    'table' => 'likeables',
    'model' => \Akira\Likeable\Likeable::class,
];
```

### Configuration Options

**uuids**
- Type: `boolean`
- Default: `false`
- Description: Enable UUID primary keys instead of auto-incrementing integers

**user_foreign_key**
- Type: `string`
- Default: `'user_id'`
- Description: The column name that references the user model

**table**
- Type: `string`
- Default: `'likeables'`
- Description: The database table name for storing likes

**model**
- Type: `string`
- Default: `\Akira\Likeable\Likeable::class`
- Description: The fully qualified class name of the Likeable model (for custom implementations)

## Preparing Your Models

To use the package, add traits to your Eloquent models:

### Making Models Likeable

Add the `Likeable` trait to any model that can be liked:

```php
use Akira\Likeable\Concerns\Likeable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Likeable;
}
```

### Making Models Likers

Add the `Liker` trait to your User model (or any model that can like):

```php
use Akira\Likeable\Concerns\Liker;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Liker;
}
```

## Verification

Verify the installation by running a simple test:

```php
use App\Models\User;
use App\Models\Post;

$user = User::first();
$post = Post::first();

$user->like($post);

// Should output: 1
echo $post->likesCount();
```

If everything is configured correctly, the like operation will succeed and the count will be accurate.

**Previous:** [Roadmap](00-roadmap.md) | **Next:** [Basic Usage](02-basic-usage.md)
