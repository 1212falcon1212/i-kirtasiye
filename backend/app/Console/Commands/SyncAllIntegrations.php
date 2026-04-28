<?php

namespace App\Console\Commands;

use App\Models\UserIntegration;
use App\Jobs\SyncErpProductsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllIntegrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products for all active ERP integrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sync for all active integrations...');

        $integrations = UserIntegration::where('status', 'active')->get();

        foreach ($integrations as $integration) {
            $this->info("Dispatching sync job for User: {$integration->user_id}, ERP: {$integration->erp_type}");
            SyncErpProductsJob::dispatch($integration);
        }

        $this->info("Dispatched {$integrations->count()} jobs.");
        Log::info("Scheduled Task: erp:sync-all dispatched {$integrations->count()} jobs.");
    }
}
