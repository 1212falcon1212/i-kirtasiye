<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Yeni iade talebi olusturuldugunda tetiklenir
 */
class ReturnRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReturnRequest $returnRequest
    ) {}
}
