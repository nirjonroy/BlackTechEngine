<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendIntegrationTestMailRequest;
use App\Http\Requests\Admin\UpdateGeminiIntegrationRequest;
use App\Http\Requests\Admin\UpdateMailIntegrationRequest;
use App\Http\Requests\Admin\UpdateStripeIntegrationRequest;
use App\Mail\IntegrationTestMail;
use App\Models\Admin;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Integrations\IntegrationSettingsManager;
use App\Services\Integrations\RuntimeIntegrationConfigurator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class IntegrationSettingController extends Controller
{
    public function __construct(
        private readonly IntegrationSettingsManager $settings,
        private readonly RuntimeIntegrationConfigurator $runtimeConfigurator,
    ) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['gemini', 'stripe', 'mail'], true)
            ? $request->query('tab')
            : 'gemini';
        $provider = AiProvider::query()->where('key', 'gemini')->first();
        $workload = AiWorkloadRoute::query()
            ->where('workload_key', AiContentGenerationManager::WORKLOAD_KEY)
            ->first();
        $setting = $this->settings->current();

        return view('admin.integrations.index', [
            'tab' => $tab,
            'setting' => $setting,
            'secretStatus' => [
                'gemini' => $setting->hasGeminiApiKey(),
                'stripe_key' => $setting->hasStripeSecretKey(),
                'stripe_webhook' => $setting->hasStripeWebhookSecret(),
                'mail' => $setting->hasMailPassword(),
            ],
            'geminiProvider' => $provider,
            'generationWorkload' => $workload,
            'geminiModels' => AiModel::query()
                ->whereHas('provider', fn ($query) => $query->where('key', 'gemini')->where('adapter_type', 'gemini'))
                ->orderBy('display_name')
                ->orderBy('id')
                ->get(['id', 'ai_provider_id', 'display_name', 'provider_model_id', 'is_enabled']),
            'webhookUrl' => route('billing.webhooks.payment'),
        ]);
    }

    public function updateGemini(UpdateGeminiIntegrationRequest $request): RedirectResponse
    {
        $this->settings->updateGemini($this->admin($request), $request->validated());

        return Redirect::route('admin.integrations.index', ['tab' => 'gemini'])
            ->with('status', 'Gemini integration settings were saved. Customer generation remains controlled by its separate feature gate.');
    }

    public function updateStripe(UpdateStripeIntegrationRequest $request): RedirectResponse
    {
        $this->settings->updateStripe($this->admin($request), $request->validated());

        return Redirect::route('admin.integrations.index', ['tab' => 'stripe'])
            ->with('status', 'Stripe settings were saved. Checkout remains controlled by its separate feature gate.');
    }

    public function updateMail(UpdateMailIntegrationRequest $request): RedirectResponse
    {
        $this->settings->updateMail($this->admin($request), $request->validated());
        $this->runtimeConfigurator->applyMailConfiguration();

        return Redirect::route('admin.integrations.index', ['tab' => 'mail'])
            ->with('status', 'Email settings were saved for web requests and queue jobs.');
    }

    public function sendTestMail(SendIntegrationTestMailRequest $request): RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $this->runtimeConfigurator->applyMailConfiguration()) {
            throw ValidationException::withMessages(['recipient' => 'Enable and complete the SMTP settings before sending a test email.']);
        }

        try {
            Mail::to($request->validated('recipient'))->send(new IntegrationTestMail);
        } catch (Throwable) {
            $this->settings->recordAction($admin, 'mail', 'integration_settings.test_failed');

            return Redirect::back()->withErrors(['recipient' => 'The test email could not be delivered. Verify the SMTP settings and try again.']);
        }

        $this->settings->recordAction($admin, 'mail', 'integration_settings.test_succeeded');

        return Redirect::route('admin.integrations.index', ['tab' => 'mail'])
            ->with('status', 'A test email was accepted by the configured mail transport.');
    }

    private function admin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
