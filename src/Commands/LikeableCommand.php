<?php

declare(strict_types=1);

namespace Akira\Likeable\Commands;

use Illuminate\Console\Command;

final class LikeableCommand extends Command
{
    public $signature = 'laravel-likeable';

    public $description = 'My command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
