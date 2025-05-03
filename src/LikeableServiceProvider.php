<?php

declare(strict_types=1);

namespace Akira\Likeable;

use Akira\Likeable\Commands\LikeableCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LikeableServiceProvider extends PackageServiceProvider
{
    /**
     * Register the service provider.
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
}
