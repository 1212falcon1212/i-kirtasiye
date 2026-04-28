<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Jobs\SendPaymentNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Odeme basarisiz oldugunda aliciya bilgilendirme e-postasi gonderir
 */
class SendPaymentFailedEmail implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        dispatch(new SendPaymentNotificationJob($event->order, false, $event->reason));
    }
}
