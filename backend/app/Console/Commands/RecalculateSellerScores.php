<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SellerScoreService;
use Illuminate\Console\Command;

class RecalculateSellerScores extends Command
{
    protected $signature = 'sellers:recalculate-scores';
    protected $description = 'Recalculate seller scores for all sellers';

    public function handle(SellerScoreService $service): int
    {
        $sellers = User::where(function ($q) {
            $q->where('role', User::ROLE_RETAILER)
              ->orWhere('role', User::ROLE_SELLER);
        })->get();

        $this->info("Recalculating scores for {$sellers->count()} sellers...");

        $bar = $this->output->createProgressBar($sellers->count());
        $bar->start();

        $updated = 0;
        foreach ($sellers as $seller) {
            $service->calculateScore($seller);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated {$updated} seller scores.");

        return self::SUCCESS;
    }
}
