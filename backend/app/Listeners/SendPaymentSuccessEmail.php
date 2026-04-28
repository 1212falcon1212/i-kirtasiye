<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SendPaymentNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Odeme basarili oldugunda aliciya bilgilendirme e-postasi gonderir
 */
class SendPaymentSuccessEmail implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        dispatch(new SendPaymentNotificationJob($event->order, true));
    }
}
