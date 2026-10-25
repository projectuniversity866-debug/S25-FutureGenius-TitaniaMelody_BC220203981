<?php

namespace App\Modules\Community\Console;

use App\Modules\Community\Services\ReputationService;
use Illuminate\Console\Command;

class RebuildReputation extends Command
{
    protected $signature = 'community:rebuild-reputation';

    protected $description = 'Recreate every member\'s reputation ledger and badges from votes and accepted answers';

    public function handle(ReputationService $reputation): int
    {
        $reputation->rebuild();

        $this->info('Reputation ledger and badges rebuilt.');

        return self::SUCCESS;
    }
}
