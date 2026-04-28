<?php

namespace App\Services\Notification\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    public function __construct(protected Messaging $messaging)
    {
    }

    /**
     * Send a push notification to a specific device token.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::error('Firebase Push Notification Failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send a push notification to a topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::error('Firebase Topic Notification Failed', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
