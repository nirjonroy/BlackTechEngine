<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePageSetting;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AffiliatePageController extends Controller
{
    public function __invoke(): View
    {
        $setting = AffiliatePageSetting::query()->first();

        return view('frontend.affiliate', [
            'setting' => $setting,
            'workflowSteps' => $this->workflowSteps($setting?->workflow_steps),
            'availabilityLabel' => AffiliatePageSetting::AVAILABILITY_OPTIONS[$setting?->availability_status ?? 'not_open']
                ?? AffiliatePageSetting::AVAILABILITY_OPTIONS['not_open'],
            'resourcesAvailabilityLabel' => AffiliatePageSetting::RESOURCE_AVAILABILITY_OPTIONS[$setting?->resources_availability_status ?? 'not_available']
                ?? AffiliatePageSetting::RESOURCE_AVAILABILITY_OPTIONS['not_available'],
            'primaryCtaUrl' => $this->destinationUrl($setting?->primary_cta_destination, 'program-status'),
            'secondaryCtaUrl' => $this->destinationUrl($setting?->secondary_cta_destination, 'register'),
            'finalCtaUrl' => $this->informationalDestinationUrl($setting?->final_cta_destination),
            'seoFallbackTitle' => 'Affiliate Program | BlackTechEngine',
            'seoFallbackDescription' => 'Review the current status of the planned BlackTechEngine affiliate program and the terms that still require approval.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $configuredSteps
     * @return Collection<int, array{icon: string, title: string, description: string}>
     */
    private function workflowSteps(?array $configuredSteps): Collection
    {
        $configured = collect($configuredSteps ?? [])->values();

        return collect(AffiliatePageSetting::DEFAULT_WORKFLOW_STEPS)
            ->map(function (array $fallback, int $index) use ($configured): array {
                $step = $configured->get($index);

                if (! is_array($step)) {
                    return $fallback;
                }

                return [
                    'icon' => $this->safeIcon($step['icon'] ?? null, $fallback['icon']),
                    'title' => $this->nonEmptyString($step['title'] ?? null) ?? $fallback['title'],
                    'description' => $this->nonEmptyString($step['description'] ?? null) ?? $fallback['description'],
                ];
            });
    }

    private function safeIcon(mixed $icon, string $fallback): string
    {
        return is_string($icon) && in_array($icon, AffiliatePageSetting::WORKFLOW_ICONS, true)
            ? $icon
            : $fallback;
    }

    private function destinationUrl(?string $destination, string $fallback): string
    {
        $destination = array_key_exists((string) $destination, AffiliatePageSetting::DESTINATION_OPTIONS)
            ? $destination
            : $fallback;

        return $destination === 'program-status'
            ? '#program-status'
            : route($destination);
    }

    private function informationalDestinationUrl(?string $destination): string
    {
        $destination = array_key_exists((string) $destination, AffiliatePageSetting::INFORMATIONAL_DESTINATION_OPTIONS)
            ? $destination
            : 'features';

        return $destination === 'program-status'
            ? '#program-status'
            : route($destination);
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && filled(trim($value)) ? $value : null;
    }
}
