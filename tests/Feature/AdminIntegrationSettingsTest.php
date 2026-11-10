<?php

use App\Mail\IntegrationTestMail;
use App\Models\Admin;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\IntegrationSetting;
use App\Models\IntegrationSettingAudit;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Ai\AiProviderCredentialResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

function integrationAdmin(string $role = 'Super Admin'): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);
    $admin->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']));

    return $admin;
}

function integrationPayload(array $overrides = []): array
{
    return [
        'api_key' => null,
        'enabled' => '0',
        'clear_api_key' => '0',
        'ai_model_id' => null,
        'workload_lock_version' => null,
        'lock_version' => 0,
        ...$overrides,
    ];
}

test('integration settings are restricted to Super Admins', function () {
    $this->get(route('admin.integrations.index'))->assertRedirectToRoute('admin.login');

    $editor = integrationAdmin('Editor');
    $this->actingAs($editor, 'admin')->get(route('admin.integrations.index'))->assertForbidden();

    $superAdmin = integrationAdmin();
    $this->actingAs($superAdmin, 'admin')->get(route('admin.integrations.index'))
        ->assertOk()
        ->assertSee('Integrations &amp; Settings', false);
});

test('Gemini secrets are encrypted audited and never rendered', function () {
    $admin = integrationAdmin();
    $secret = 'synthetic-gemini-secret-value';
    $provider = AiProvider::factory()->create(['key' => 'gemini', 'adapter_type' => 'gemini']);
    $model = AiModel::factory()->for($provider, 'provider')->create();
    $workload = AiWorkloadRoute::factory()->create([
        'workload_key' => AiContentGenerationManager::WORKLOAD_KEY,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.integrations.gemini.update'), integrationPayload([
            'api_key' => $secret,
            'enabled' => '1',
            'ai_model_id' => $model->id,
            'workload_lock_version' => 0,
        ]))
        ->assertRedirectToRoute('admin.integrations.index', ['tab' => 'gemini'])
        ->assertSessionHasNoErrors();

    $setting = IntegrationSetting::query()->sole();
    $rawSecret = DB::table('integration_settings')->value('gemini_api_key');
    $audit = IntegrationSettingAudit::query()->sole();

    expect($setting->gemini_api_key)->toBe($secret)
        ->and($setting->gemini_enabled)->toBeTrue()
        ->and($rawSecret)->not->toBe($secret)
        ->and($workload->fresh()->primary_ai_model_id)->toBe($model->id)
        ->and(json_encode($audit->changed_fields))->not->toContain($secret)
        ->and(app(AiProviderCredentialResolver::class)->geminiApiKey())->toBe($secret);

    $this->get(route('admin.integrations.index'))->assertOk()->assertDontSee($secret);
});

test('unrelated integration settings preserve the legacy environment Gemini credential', function () {
    config()->set('services.gemini.api_key', 'environment-gemini-key');
    IntegrationSetting::factory()->create(['id' => 1, 'stripe_mode' => 'test']);

    expect(app(AiProviderCredentialResolver::class)->geminiApiKey())->toBe('environment-gemini-key');
});

test('blank Gemini input preserves the secret and explicit clearing disables it', function () {
    $admin = integrationAdmin();
    $setting = IntegrationSetting::factory()->create([
        'id' => 1,
        'gemini_api_key' => 'preserved-secret',
        'gemini_enabled' => true,
    ]);

    $this->actingAs($admin, 'admin')->patch(
        route('admin.integrations.gemini.update'),
        integrationPayload(['enabled' => '1', 'lock_version' => $setting->lock_version]),
    )->assertSessionHasNoErrors();

    expect($setting->fresh()->gemini_api_key)->toBe('preserved-secret');

    $this->patch(route('admin.integrations.gemini.update'), integrationPayload([
        'clear_api_key' => '1',
        'lock_version' => $setting->fresh()->lock_version,
    ]))->assertSessionHasNoErrors();

    expect($setting->fresh()->gemini_api_key)->toBeNull()
        ->and($setting->fresh()->gemini_enabled)->toBeFalse();
});

test('Gemini credentials cannot be disabled ahead of the cascading provider deactivation', function () {
    $admin = integrationAdmin();
    $setting = IntegrationSetting::factory()->create([
        'id' => 1,
        'gemini_api_key' => 'preserved-secret',
        'gemini_enabled' => true,
    ]);
    AiProvider::factory()->create(['key' => 'gemini', 'adapter_type' => 'gemini', 'is_enabled' => true]);

    $this->actingAs($admin, 'admin')->patch(
        route('admin.integrations.gemini.update'),
        integrationPayload(['lock_version' => $setting->lock_version]),
    )->assertSessionHasErrors('enabled');

    expect($setting->fresh()->gemini_enabled)->toBeTrue()
        ->and($setting->fresh()->gemini_api_key)->toBe('preserved-secret');
});

test('Stripe secrets are mode matched encrypted and blank values are preserved', function () {
    $admin = integrationAdmin();
    $payload = [
        'mode' => 'test',
        'publishable_key' => 'pk_test_synthetic',
        'secret_key' => 'sk_test_synthetic-secret',
        'webhook_secret' => 'whsec_synthetic-secret',
        'enabled' => '1',
        'clear_secrets' => '0',
        'lock_version' => 0,
    ];

    $this->actingAs($admin, 'admin')->patch(route('admin.integrations.stripe.update'), $payload)
        ->assertSessionHasNoErrors();

    $setting = IntegrationSetting::query()->sole();
    $raw = DB::table('integration_settings')->first();
    expect($setting->stripe_enabled)->toBeTrue()
        ->and($raw->stripe_secret_key)->not->toContain('sk_test_synthetic')
        ->and($raw->stripe_webhook_secret)->not->toContain('whsec_synthetic');

    $this->patch(route('admin.integrations.stripe.update'), [
        ...$payload,
        'secret_key' => null,
        'webhook_secret' => null,
        'lock_version' => $setting->fresh()->lock_version,
    ])->assertSessionHasNoErrors();

    expect($setting->fresh()->stripe_secret_key)->toBe('sk_test_synthetic-secret')
        ->and($setting->fresh()->stripe_webhook_secret)->toBe('whsec_synthetic-secret');
});

test('database SMTP configuration is applied and test mail is audited without secrets', function () {
    $admin = integrationAdmin();

    $this->actingAs($admin, 'admin')->patch(route('admin.integrations.mail.update'), [
        'host' => 'smtp.example.test',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'mailer@example.test',
        'password' => 'smtp-synthetic-secret',
        'from_name' => 'BlackTechEngine',
        'from_address' => 'noreply@example.test',
        'reply_to' => 'support@example.test',
        'enabled' => '1',
        'clear_password' => '0',
        'lock_version' => 0,
    ])->assertSessionHasNoErrors();

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.test')
        ->and(config('mail.from.address'))->toBe('noreply@example.test')
        ->and(DB::table('integration_settings')->value('mail_password'))->not->toContain('smtp-synthetic-secret');

    Mail::fake();
    $this->post(route('admin.integrations.mail.test'), ['recipient' => 'admin@example.test'])
        ->assertRedirectToRoute('admin.integrations.index', ['tab' => 'mail'])
        ->assertSessionHasNoErrors();

    Mail::assertSent(IntegrationTestMail::class, 1);
    expect(IntegrationSettingAudit::query()->latest('id')->value('action'))
        ->toBe('integration_settings.test_succeeded');
});

test('integration updates reject forged operational fields and stale versions', function () {
    $admin = integrationAdmin();
    IntegrationSetting::factory()->create(['id' => 1, 'lock_version' => 2]);

    $this->actingAs($admin, 'admin')->patch(route('admin.integrations.gemini.update'), integrationPayload([
        'lock_version' => 2,
        'is_enabled' => '1',
    ]))->assertSessionHasErrors('is_enabled');

    $this->patch(route('admin.integrations.gemini.update'), integrationPayload([
        'lock_version' => 1,
    ]))->assertSessionHasErrors('lock_version');

    $this->assertDatabaseCount('integration_setting_audits', 0);
});

test('integration mutations require CSRF protection outside the test environment', function () {
    $admin = integrationAdmin();
    $this->actingAs($admin, 'admin');
    $this->app['env'] = 'production';

    $this->patch(route('admin.integrations.gemini.update'), integrationPayload([
        'api_key' => 'must-not-be-stored',
        'enabled' => '1',
    ]))->assertStatus(419);

    $this->assertDatabaseCount('integration_settings', 0);
});
