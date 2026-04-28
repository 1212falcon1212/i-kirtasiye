<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ReturnRequestCreated;
use App\Jobs\SendReturnRequestNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Yeni iade talebi olusturuldugunda saticiya e-posta gonderir
 */
class SendReturnRequestEmail implements ShouldQueue
{
    public function handle(ReturnRequestCreated $event): void
    {
        dispatch(new SendReturnRequestNotificationJob($event->returnRequest));
    }
}
