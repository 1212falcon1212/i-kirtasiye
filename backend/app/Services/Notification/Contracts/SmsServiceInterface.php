<?php

namespace App\Services\Notification\Contracts;

interface SmsServiceInterface
{
    /**
     * Send an SMS message to a single recipient.
     *
     * @param string $phone The phone number (e.g., 5xxxxxxxxx)
     * @param string $message The message content
     * @return bool True if sent successfully (or accepted by provider)
     */
    public function send(string $phone, string $message): bool;
}
