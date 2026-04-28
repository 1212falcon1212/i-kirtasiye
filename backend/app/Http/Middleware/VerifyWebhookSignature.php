<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * IP whitelist for webhook providers
     */
    protected array $ipWhitelist = [
        'iyzico' => [
            '34.90.19.141',
            '34.90.158.241',
            '35.204.176.184',
            '35.204.47.206',
            '35.205.25.0',
            '35.233.99.113',
            '35.240.44.213',
            '35.242.201.22',
            '35.242.236.36',
            '35.242.242.202',
        ],
        'paytr' => [
            '185.46.132.0/24',
            '185.46.133.0/24',
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $provider = $request->route('provider');

        if (!$provider) {
            Log::warning('Webhook received without provider');
            return response()->json(['error' => 'Provider not specified'], 400);
        }

        // Check IP whitelist
        if (!$this->verifyIpWhitelist($request, $provider)) {
            Log::warning("Webhook IP not whitelisted", [
                'provider' => $provider,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized IP address'], 403);
        }

        // Verify signature based on provider
        if (!$this->verifySignature($request, $provider)) {
            Log::warning("Webhook signature verification failed", [
                'provider' => $provider,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        Log::info("Webhook signature verified", [
            'provider' => $provider,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Verify IP is in whitelist for provider
     */
    protected function verifyIpWhitelist(Request $request, string $provider): bool
    {
        // In development/testing, skip IP verification
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $clientIp = $request->ip();
        $whitelist = $this->ipWhitelist[$provider] ?? [];

        // If no whitelist defined for provider, allow all (for generic webhooks)
        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches (supports CIDR notation)
     */
    protected function ipMatches(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation
        if (str_contains($range, '/')) {
            [$subnet, $mask] = explode('/', $range);
            $mask = (int) $mask;

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);

            if ($ipLong === false || $subnetLong === false) {
                return false;
            }

            $maskLong = -1 << (32 - $mask);
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        return false;
    }

    /**
     * Verify webhook signature based on provider
     */
    protected function verifySignature(Request $request, string $provider): bool
    {
        return match ($provider) {
            'iyzico' => $this->verifyIyzicoSignature($request),
            'paytr' => $this->verifyPaytrSignature($request),
            'entegra', 'bizimhesap', 'sentos', 'parasut' => $this->verifyErpSignature($request, $provider),
            default => $this->verifyGenericSignature($request, $provider),
        };
    }

    /**
     * Verify Iyzico webhook signature
     */
    protected function verifyIyzicoSignature(Request $request): bool
    {
        $secretKey = Setting::getValue('payment.iyzico_secret_key', '');

        if (empty($secretKey)) {
            Log::warning('Iyzico secret key not configured');
            return false;
        }

        // Iyzico uses iyziEventType header and signature verification
        $signature = $request->header('X-IYZ-Signature');
        $eventType = $request->header('X-IYZ-Event-Type');

        if (!$signature) {
            // For callback requests without signature header, verify via token
            $token = $request->input('token');
            return !empty($token);
        }

        // Build signature string from payload
        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secretKey, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify PayTR webhook signature
     */
    protected function verifyPaytrSignature(Request $request): bool
    {
        $merchantKey = Setting::getValue('payment.paytr_merchant_key', '');
        $merchantSalt = Setting::getValue('payment.paytr_merchant_salt', '');

        if (empty($merchantKey) || empty($merchantSalt)) {
            Log::warning('PayTR credentials not configured');
            return false;
        }

        $hash = $request->input('hash');
        $merchantOid = $request->input('merchant_oid');
        $status = $request->input('status');
        $totalAmount = $request->input('total_amount');

        if (!$hash || !$merchantOid) {
            return false;
        }

        // Verify hash according to PayTR documentation
        $expectedHash = base64_encode(hash_hmac(
            'sha256',
            $merchantOid . $merchantSalt . $status . $totalAmount,
            $merchantKey,
            true
        ));

        return hash_equals($expectedHash, $hash);
    }

    /**
     * Verify ERP provider webhook signature
     */
    protected function verifyErpSignature(Request $request, string $provider): bool
    {
        // ERP webhooks typically use a shared secret in header
        $webhookSecret = Setting::getValue("erp.{$provider}_webhook_secret", '');

        if (empty($webhookSecret)) {
            // If no webhook secret configured, allow in development
            return app()->environment('local', 'testing');
        }

        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('Authorization');

        if (!$signature) {
            return false;
        }

        // Support Bearer token format
        if (str_starts_with($signature, 'Bearer ')) {
            $signature = substr($signature, 7);
        }

        // HMAC signature verification
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify generic webhook signature
     */
    protected function verifyGenericSignature(Request $request, string $provider): bool
    {
        $webhookSecret = Setting::getValue("webhooks.{$provider}_secret", '');

        if (empty($webhookSecret)) {
            // Allow in development if no secret configured
            return app()->environment('local', 'testing');
        }

        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature');

        if (!$signature) {
            return false;
        }

        // Handle sha256= prefix (GitHub style)
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
