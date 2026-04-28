<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Siparis odemesi basarisiz oldugunda tetiklenir
 */
class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $reason = ''
    ) {}
}
