<?php

namespace App\Services\Integrations;

use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\IntegrationSetting;
use App\Models\IntegrationSettingAudit;
use App\Models\PaymentTransaction;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Ai\Gemini\GeminiProviderAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationSettingsManager
{
    public function current(): IntegrationSetting
    {
        return IntegrationSetting::query()->find(1) ?? new IntegrationSetting(['id' => 1]);
    }

    /** @param list<string> $changedFields */
    public function recordAction(Admin $admin, string $integration, string $action, array $changedFields = []): void
    {
        DB::transaction(function () use ($admin, $integration, $action, $changedFields): void {
            $setting = $this->lockedSetting();
            IntegrationSettingAudit::query()->create([
                'acting_admin_id' => $admin->getAuthIdentifier(),
                'integration_setting_id' => $setting->getKey(),
                'integration' => $integration,
                'action' => $action,
                'request_id' => Str::uuid()->toString(),
                'changed_fields' => $changedFields,
            ]);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateGemini(Admin $admin, array $data): IntegrationSetting
    {
        return DB::transaction(function () use ($admin, $data): IntegrationSetting {
            $setting = $this->lockedSetting();
            $this->ensureCurrentVersion($setting, (int) $data['lock_version']);
            $setting->gemini_managed = true;

            if ((bool) $data['clear_api_key']) {
                $setting->gemini_api_key = null;
                $setting->gemini_enabled = false;
            } else {
                if (filled($data['api_key'] ?? null)) {
                    $setting->gemini_api_key = trim((string) $data['api_key']);
                }

                $setting->gemini_enabled = (bool) $data['enabled'];
            }

            if ($setting->gemini_enabled && blank($setting->gemini_api_key)) {
                throw ValidationException::withMessages(['enabled' => 'Store a Gemini API key before enabling this integration.']);
            }

            if (! $setting->gemini_enabled && AiProvider::query()->where('key', 'gemini')->where('is_enabled', true)->exists()) {
                throw ValidationException::withMessages(['enabled' => 'Deactivate the Gemini provider first so dependent models and workloads are disabled safely.']);
            }

            $this->saveSetting($admin, $setting, 'gemini', array_keys($setting->getDirty()));
            $this->updateGenerationModel($admin, $data);

            return $setting->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateStripe(Admin $admin, array $data): IntegrationSetting
    {
        return DB::transaction(function () use ($admin, $data): IntegrationSetting {
            $setting = $this->lockedSetting();
            $this->ensureCurrentVersion($setting, (int) $data['lock_version']);
            $setting->stripe_mode = $data['mode'];
            $setting->stripe_publishable_key = filled($data['publishable_key'] ?? null)
                ? trim((string) $data['publishable_key'])
                : null;

            foreach (['secret_key' => 'stripe_secret_key', 'webhook_secret' => 'stripe_webhook_secret'] as $input => $attribute) {
                if ((bool) $data['clear_secrets']) {
                    $setting->{$attribute} = null;
                } elseif (filled($data[$input] ?? null)) {
                    $setting->{$attribute} = trim((string) $data[$input]);
                }
            }

            $setting->stripe_enabled = (bool) $data['enabled'] && ! (bool) $data['clear_secrets'];

            if ($setting->stripe_enabled && ! $this->stripeCredentialsMatchMode($setting)) {
                throw ValidationException::withMessages(['enabled' => 'Complete matching Stripe keys and a webhook secret before enabling Stripe.']);
            }

            if (! $setting->stripe_enabled
                && PaymentTransaction::query()->where('provider', 'stripe')->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['enabled' => 'Stripe cannot be disabled while Checkout payments are pending.']);
            }

            $this->saveSetting($admin, $setting, 'stripe', array_keys($setting->getDirty()));

            return $setting->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateMail(Admin $admin, array $data): IntegrationSetting
    {
        return DB::transaction(function () use ($admin, $data): IntegrationSetting {
            $setting = $this->lockedSetting();
            $this->ensureCurrentVersion($setting, (int) $data['lock_version']);

            foreach (['host', 'port', 'encryption', 'username', 'from_name', 'from_address', 'reply_to'] as $field) {
                $setting->{'mail_'.$field} = filled($data[$field] ?? null) ? $data[$field] : null;
            }

            if ((bool) $data['clear_password']) {
                $setting->mail_password = null;
            } elseif (filled($data['password'] ?? null)) {
                $setting->mail_password = (string) $data['password'];
            }

            $setting->mail_enabled = (bool) $data['enabled'];

            if ($setting->mail_enabled && (blank($setting->mail_host)
                || blank($setting->mail_port)
                || blank($setting->mail_from_address))) {
                throw ValidationException::withMessages(['enabled' => 'Host, port, and From Email are required before enabling database mail settings.']);
            }

            $this->saveSetting($admin, $setting, 'mail', array_keys($setting->getDirty()));

            return $setting->refresh();
        }, 3);
    }

    private function lockedSetting(): IntegrationSetting
    {
        $setting = IntegrationSetting::query()->lockForUpdate()->find(1);

        if ($setting instanceof IntegrationSetting) {
            return $setting;
        }

        $setting = new IntegrationSetting;
        $setting->setAttribute('id', 1);
        $setting->save();

        return $setting;
    }

    private function ensureCurrentVersion(IntegrationSetting $setting, int $expectedVersion): void
    {
        if ($setting->lock_version !== $expectedVersion) {
            throw ValidationException::withMessages(['lock_version' => 'Integration settings changed after this page was opened. Refresh and try again.']);
        }
    }

    /** @param list<string> $changedFields */
    private function saveSetting(Admin $admin, IntegrationSetting $setting, string $integration, array $changedFields): void
    {
        if ($changedFields === []) {
            return;
        }

        $safeFields = array_values(array_diff($changedFields, [
            'gemini_api_key', 'stripe_secret_key', 'stripe_webhook_secret', 'mail_password',
        ]));

        foreach (['gemini_api_key', 'stripe_secret_key', 'stripe_webhook_secret', 'mail_password'] as $secretField) {
            if (in_array($secretField, $changedFields, true)) {
                $safeFields[] = $secretField.'_changed';
            }
        }

        $setting->lock_version++;
        $setting->save();
        IntegrationSettingAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'integration_setting_id' => $setting->getKey(),
            'integration' => $integration,
            'action' => 'integration_settings.updated',
            'request_id' => Str::uuid()->toString(),
            'changed_fields' => array_values(array_unique($safeFields)),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function updateGenerationModel(Admin $admin, array $data): void
    {
        if (($data['ai_model_id'] ?? null) === null) {
            return;
        }

        $model = AiModel::query()->with('provider')->findOrFail((int) $data['ai_model_id']);

        if (! $model->provider instanceof AiProvider
            || $model->provider->key !== GeminiProviderAdapter::IDENTIFIER
            || $model->provider->adapter_type !== GeminiProviderAdapter::IDENTIFIER) {
            throw ValidationException::withMessages(['ai_model_id' => 'Select a model owned by the trusted Gemini provider.']);
        }

        $route = AiWorkloadRoute::query()
            ->where('workload_key', AiContentGenerationManager::WORKLOAD_KEY)
            ->lockForUpdate()
            ->first();

        if (! $route instanceof AiWorkloadRoute) {
            throw ValidationException::withMessages(['ai_model_id' => 'Create the SEO/GEO workload draft before selecting its model here.']);
        }

        if ($route->is_enabled) {
            throw ValidationException::withMessages(['ai_model_id' => 'Deactivate the workload before changing its model.']);
        }

        if ($route->lock_version !== (int) $data['workload_lock_version']) {
            throw ValidationException::withMessages(['ai_model_id' => 'The workload changed after this page was opened. Refresh and try again.']);
        }

        if ($route->primary_ai_model_id === $model->getKey()) {
            return;
        }

        $route->primary_ai_model_id = $model->getKey();
        $route->lock_version++;
        $route->save();
        AiConfigurationAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'subject_type' => $route::class,
            'subject_id' => $route->getKey(),
            'action' => 'ai_workload_route.model_selected',
            'request_id' => Str::uuid()->toString(),
            'changes' => ['changed_fields' => ['primary_ai_model_id']],
        ]);
    }

    private function stripeCredentialsMatchMode(IntegrationSetting $setting): bool
    {
        $prefix = $setting->stripe_mode === 'live' ? 'live' : 'test';

        return str_starts_with((string) $setting->stripe_publishable_key, 'pk_'.$prefix.'_')
            && str_starts_with((string) $setting->stripe_secret_key, 'sk_'.$prefix.'_')
            && str_starts_with((string) $setting->stripe_webhook_secret, 'whsec_');
    }
}
