<?php

namespace App\Services\Notification\Sms;

use App\Services\Notification\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\SystemSetting;

class NetgsmProvider implements SmsServiceInterface
{
    protected string $baseUrl = 'https://api.netgsm.com.tr/sms/send/get';

    public function send(string $phone, string $message): bool
    {
        // Try to get from Database first, fallback to Config
        $username = SystemSetting::where('key', 'netgsm_user')->value('value') ?? config('services.netgsm.user');
        $password = SystemSetting::where('key', 'netgsm_password')->value('value') ?? config('services.netgsm.password');
        $header = SystemSetting::where('key', 'netgsm_header')->value('value') ?? config('services.netgsm.header');

        if (!$username || !$password || !$header) {
            Log::warning('Netgsm credentials missing. SMS not sent.', [
                'phone' => $phone,
                'message' => $message
            ]);
            return false;
        }

        // Netgsm format: 5xxxxxxxxx
        // Remove leading 0 or +90 if present
        $phone = $this->formatPhone($phone);

        try {
            $response = Http::get($this->baseUrl, [
                'usercode' => $username,
                'password' => $password,
                'gsmno' => $phone,
                'message' => $message,
                'msgheader' => $header,
                // 'dil' => 'TR' // Optional for Turkish chars
            ]);

            // Netgsm returns ID on success (e.g., "00 123456") or error code
            $body = $response->body();

            // Check if response starts with "00", "01", "02" (Success codes)
            // Error codes usually start with "20", "30", etc.
            if (preg_match('/^[0-9]{2} [0-9]+$/', $body) || str_starts_with($body, '00')) {
                Log::info('Netgsm SMS sent successfully.', ['phone' => $phone, 'response' => $body]);
                return true;
            }

            Log::error('Netgsm SMS failed.', ['phone' => $phone, 'response' => $body]);
            return false;

        } catch (\Exception $e) {
            Log::error('Netgsm API exception.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function formatPhone(string $phone): string
    {
        // Simple formatter
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '90')) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        return $phone;
    }
}
