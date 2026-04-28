<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Alt siparis durumu degistiginde tetiklenir (confirmed, shipped, delivered)
 */
class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public SubOrder $subOrder,
        public string $newStatus,
        public string $sellerName
    ) {}
}
