<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderAdapter;
use App\Enums\AiProviderErrorCategory;
use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiProvider;
use App\Services\Ai\Gemini\GeminiProviderAdapter;
use App\Services\Ai\Results\ModelDiscoveryResult;
use App\Services\Ai\Results\ProviderVerificationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiProviderConnectionManager
{
    public function __construct(
        private readonly AiProviderAdapterRegistry $adapterRegistry,
    ) {}

    public function bind(Admin $admin, AiProvider $provider, string $adapterIdentifier, int $expectedVersion): bool
    {
        $adapter = $this->adapterRegistry->resolve($adapterIdentifier);

        return DB::transaction(function () use ($admin, $provider, $adapter, $expectedVersion): bool {
            $lockedProvider = AiProvider::query()->lockForUpdate()->findOrFail($provider->getKey());

            $this->ensureCurrentVersion($lockedProvider, $expectedVersion);

            if ($lockedProvider->key !== GeminiProviderAdapter::IDENTIFIER
                || $adapter->identifier() !== GeminiProviderAdapter::IDENTIFIER) {
                throw ValidationException::withMessages([
                    'adapter_identifier' => 'The Gemini adapter can only be bound to the normalized gemini provider.',
                ]);
            }

            if ($lockedProvider->adapter_type === $adapter->identifier()) {
                return false;
            }

            if ($lockedProvider->adapter_type !== null) {
                throw ValidationException::withMessages([
                    'adapter_identifier' => 'This provider already has a different trusted adapter binding.',
                ]);
            }

            $lockedProvider->adapter_type = $adapter->identifier();
            $lockedProvider->lock_version++;
            $lockedProvider->save();

            $this->recordAudit(
                $admin,
                $lockedProvider,
                'ai_provider.adapter_bound',
                ['adapter_type'],
                'trusted_adapter_bound',
            );

            return true;
        });
    }

    public function verify(Admin $admin, AiProvider $provider, int $expectedVersion): ProviderVerificationResult
    {
        $provider->refresh();
        $this->ensureCurrentVersion($provider, $expectedVersion);
        $adapter = $this->boundAdapter($provider);

        $result = $adapter->verifyConnection();

        return DB::transaction(function () use ($admin, $provider, $expectedVersion, $result): ProviderVerificationResult {
            $lockedProvider = AiProvider::query()->lockForUpdate()->findOrFail($provider->getKey());

            $this->ensureCurrentVersion($lockedProvider, $expectedVersion);
            $this->boundAdapter($lockedProvider);

            if ($result->successful) {
                $lockedProvider->last_verified_at = now();
                $lockedProvider->lock_version++;
                $lockedProvider->save();

                $this->recordAudit(
                    $admin,
                    $lockedProvider,
                    'ai_provider.connection_verified',
                    ['last_verified_at'],
                    'models_endpoint_verified',
                );

                return $result;
            }

            $this->recordAudit(
                $admin,
                $lockedProvider,
                'ai_provider.connection_verification_failed',
                [],
                ($result->errorCategory ?? AiProviderErrorCategory::ProviderUnavailable)->value,
            );

            return $result;
        });
    }

    public function discoverModels(AiProvider $provider): ModelDiscoveryResult
    {
        $provider->refresh();

        return $this->boundAdapter($provider)->discoverModels();
    }

    private function boundAdapter(AiProvider $provider): AiProviderAdapter
    {
        if ($provider->key !== GeminiProviderAdapter::IDENTIFIER
            || $provider->adapter_type !== GeminiProviderAdapter::IDENTIFIER) {
            throw ValidationException::withMessages([
                'verification' => 'This provider is not bound to the trusted Gemini adapter.',
            ]);
        }

        return $this->adapterRegistry->resolve($provider->adapter_type);
    }

    private function ensureCurrentVersion(AiProvider $provider, int $expectedVersion): void
    {
        if ($provider->lock_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'lock_version' => 'This provider changed after you opened the page. Review the latest state and try again.',
            ]);
        }
    }

    /** @param list<string> $changedFields */
    private function recordAudit(
        Admin $admin,
        AiProvider $provider,
        string $action,
        array $changedFields,
        string $verificationOutcome,
    ): void {
        AiConfigurationAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'subject_type' => $provider::class,
            'subject_id' => $provider->getKey(),
            'action' => $action,
            'request_id' => Str::uuid()->toString(),
            'changes' => [
                'changed_fields' => $changedFields,
                'verification_outcome' => $verificationOutcome,
            ],
        ]);
    }
}
