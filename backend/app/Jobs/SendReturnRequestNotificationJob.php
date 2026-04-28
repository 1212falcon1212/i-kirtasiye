<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ReturnRequestNotificationMail;
use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Saticiya iade talebi bildirim e-postasi gonderir
 */
class SendReturnRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ReturnRequest $returnRequest
    ) {}

    public function handle(): void
    {
        $this->returnRequest->load('order', 'buyer', 'seller', 'orderItem.product');

        $seller = $this->returnRequest->seller;

        if (! $seller?->email) {
            Log::warning('Return request notification skipped: no seller email', [
                'return_request_id' => $this->returnRequest->id,
                'seller_id' => $this->returnRequest->seller_id,
            ]);

            return;
        }

        Mail::to($seller->email)
            ->send(new ReturnRequestNotificationMail($this->returnRequest));

        Log::info('Return request notification email sent to seller', [
            'return_request_id' => $this->returnRequest->id,
            'seller_id' => $seller->id,
            'email' => $seller->email,
        ]);
    }
}
