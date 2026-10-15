<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\Messaging\CampaignService;
use Illuminate\Console\Command;

class MarketingSend extends Command
{
    protected $signature = 'marketing:send {--batch=100}';

    protected $description = 'Send pending SMS / email campaign messages in batches';

    public function handle(CampaignService $campaigns): int
    {
        foreach (Campaign::where('status', 'sending')->orderBy('started_at')->get() as $c) {
            $left = $campaigns->sendBatch($c, (int) $this->option('batch'));
            $this->line("{$c->name}: {$left} left");
        }

        return self::SUCCESS;
    }
}
