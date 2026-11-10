<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RuntimeIntegrationConfigurator
{
    /** @var array<string, mixed> */
    private array $baselineMailConfiguration;

    public function __construct()
    {
        $this->baselineMailConfiguration = config('mail');
    }

    public function applyMailConfiguration(): bool
    {
        if (! Schema::hasTable('integration_settings')) {
            $this->restoreBaseline();

            return false;
        }

        $setting = IntegrationSetting::query()->find(1);

        if (! $setting instanceof IntegrationSetting
            || ! $setting->mail_enabled
            || blank($setting->mail_host)
            || blank($setting->mail_port)
            || blank($setting->mail_from_address)) {
            $this->restoreBaseline();

            return false;
        }

        try {
            config()->set([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.scheme' => $setting->mail_encryption === 'ssl' ? 'smtps' : null,
                'mail.mailers.smtp.host' => $setting->mail_host,
                'mail.mailers.smtp.port' => $setting->mail_port,
                'mail.mailers.smtp.username' => $setting->mail_username,
                'mail.mailers.smtp.password' => $setting->mail_password,
                'mail.from.address' => $setting->mail_from_address,
                'mail.from.name' => $setting->mail_from_name ?: config('app.name'),
            ]);
        } catch (DecryptException) {
            $this->restoreBaseline();

            return false;
        }

        Mail::purge('smtp');

        if (filled($setting->mail_reply_to)) {
            Mail::alwaysReplyTo($setting->mail_reply_to);
        }

        return true;
    }

    private function restoreBaseline(): void
    {
        config()->set('mail', $this->baselineMailConfiguration);
        Mail::forgetMailers();
    }
}
