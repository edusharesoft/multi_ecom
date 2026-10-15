<?php

namespace App\Console\Commands;

use App\Services\Saas\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    protected $signature = 'saas:process';

    protected $description = 'Send renewal/expiry reminders, create renewal invoices and apply grace/expiry rules';

    public function handle(SubscriptionService $service): int
    {
        $stats = $service->processAll();
        foreach ($stats as $k => $v) {
            $this->line(str_pad($k, 12).": {$v}");
        }

        return self::SUCCESS;
    }
}
