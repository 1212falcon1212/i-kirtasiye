<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendOrderConfirmationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Siparis olusturuldugunda aliciya ve saticilara e-posta gonderir
 */
class SendOrderEmails implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        dispatch(new SendOrderConfirmationJob($event->order));
    }
}
