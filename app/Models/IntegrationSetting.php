<?php

namespace App\Models;

use Database\Factories\IntegrationSettingFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'gemini_api_key', 'gemini_managed', 'gemini_enabled', 'stripe_mode', 'stripe_publishable_key',
    'stripe_secret_key', 'stripe_webhook_secret', 'stripe_enabled', 'mail_host',
    'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_name',
    'mail_from_address', 'mail_reply_to', 'mail_enabled', 'lock_version',
])]
class IntegrationSetting extends Model
{
    /** @use HasFactory<IntegrationSettingFactory> */
    use HasFactory;

    protected $attributes = [
        'gemini_enabled' => false,
        'gemini_managed' => false,
        'stripe_mode' => 'test',
        'stripe_enabled' => false,
        'mail_enabled' => false,
        'lock_version' => 0,
    ];

    protected $hidden = [
        'gemini_api_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'mail_password',
    ];

    public function hasGeminiApiKey(): bool
    {
        return $this->hasReadableSecret('gemini_api_key');
    }

    public function hasStripeSecretKey(): bool
    {
        return $this->hasReadableSecret('stripe_secret_key');
    }

    public function hasStripeWebhookSecret(): bool
    {
        return $this->hasReadableSecret('stripe_webhook_secret');
    }

    public function hasMailPassword(): bool
    {
        return $this->hasReadableSecret('mail_password');
    }

    private function hasReadableSecret(string $attribute): bool
    {
        try {
            return filled($this->getAttribute($attribute));
        } catch (DecryptException) {
            return false;
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gemini_api_key' => 'encrypted',
            'gemini_managed' => 'boolean',
            'gemini_enabled' => 'boolean',
            'stripe_secret_key' => 'encrypted',
            'stripe_webhook_secret' => 'encrypted',
            'stripe_enabled' => 'boolean',
            'mail_password' => 'encrypted',
            'mail_enabled' => 'boolean',
            'mail_port' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
