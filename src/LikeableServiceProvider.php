<?php

declare(strict_types=1);

namespace Akira\Likeable;

use Akira\Likeable\Commands\LikeableCommand;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LikeableServiceProvider extends PackageServiceProvider
{
    /**
     *  Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-likeable')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_likeable_table')
            ->hasCommand(LikeableCommand::class);
    }

    /**
     *  Register bindings.
     *
     * @throws InvalidPackage
     */
    public function register(): void
    {
        $this->app->singleton('likeable.model', fn () => resolve(type(config('likeable.model', Likeable::class))->asString()));

        $this->app->singleton('likeable.user_foreign_key', fn (): string => type(config('likeable.user_foreign_key', 'user_id'))->asString());

        parent::register();
    }
}
