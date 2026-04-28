<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendShippingNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Alt siparis kargoya verildiginde aliciya e-posta gonderir
 */
class SendShippingEmail implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->newStatus === 'shipped') {
            dispatch(new SendShippingNotificationJob($event->order, $event->subOrder, $event->sellerName));
        }
    }
}
