<?php

namespace App\Console\Commands;

use App\Services\Courier\ShipmentService;
use Illuminate\Console\Command;

class CourierSync extends Command
{
    protected $signature = 'courier:sync {--limit=200}';

    protected $description = 'Pull latest parcel status from Steadfast / Pathao / RedX for open shipments';

    public function handle(ShipmentService $shipments): int
    {
        [$ok, $fail] = $shipments->syncOpen(null, (int) $this->option('limit'));
        $this->info("Synced {$ok}, failed {$fail}");

        return self::SUCCESS;
    }
}
