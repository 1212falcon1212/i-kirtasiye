<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'erp_type',
        'api_key',
        'api_secret',
        'app_id',
        'extra_params',
        'last_sync_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'app_id' => 'encrypted',
        'extra_params' => 'encrypted:array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get masked credentials for frontend display
     */
    public function getMaskedCredentials(): array
    {
        $extras = $this->extra_params ?? [];

        return [
            'api_key' => $this->maskValue($this->api_key),
            'api_secret' => $this->maskValue($this->api_secret),
            'app_id' => $this->maskValue($this->app_id),
            'username' => $extras['username'] ?? null,
            'password' => $this->maskValue($extras['password'] ?? null),
            'test_mode' => $extras['test_mode'] ?? false,
            'wsdl_url' => $extras['wsdl_url'] ?? null,
        ];
    }

    /**
     * Mask sensitive value for display
     */
    private function maskValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        // Show first 2 and last 2 characters
        return substr($value, 0, 2) . str_repeat('•', $length - 4) . substr($value, -2);
    }

    /**
     * Get the user that owns the integration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
