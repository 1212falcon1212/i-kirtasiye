<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Api\CmsController;
use Illuminate\Console\Command;

class ClearCmsCache extends Command
{
    protected $signature = 'cms:clear-cache';

    protected $description = 'Tum CMS cache\'lerini temizler (deploy sonrasi kullanilir)';

    public function handle(): int
    {
        $cleared = CmsController::clearAllCaches();

        $this->info("CMS cache temizlendi ({$cleared} key).");

        return self::SUCCESS;
    }
}
