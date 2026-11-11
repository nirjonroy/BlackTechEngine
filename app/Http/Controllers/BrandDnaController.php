<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandDnaProfile;
use App\Models\Workspace;
use App\Services\BrandDna\BrandDnaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class BrandDnaController extends Controller
{
    public function __construct(
        private readonly BrandDnaService $dnaService,
    ) {}

    /**
     * Show the Brand DNA management dashboard.
     */
    public function show(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);
        $this->ensureBelongsToWorkspace($workspace, $brand);

        $brand->load(['brandDna.approver', 'latestBrandDnaProfile.creator']);
        $approvedProfile = $brand->brandDna;
        $latestProfile = $brand->latestBrandDnaProfile;

        return view('brands.brand-dna', compact('workspace', 'brand', 'approvedProfile', 'latestProfile'));
    }

    /**
     * Analyze website and generate a new Brand DNA profile.
     */
    public function analyze(Request $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        $this->ensureBelongsToWorkspace($workspace, $brand);

        $validated = $request->validate([
            'custom_urls' => ['nullable', 'string', 'max:2000'],
        ]);

        $customUrls = null;
        if (! empty($validated['custom_urls'])) {
            $lines = preg_split('/[\r\n,]+/', $validated['custom_urls']);
            $customUrls = array_values(array_filter(array_map('trim', (array) $lines)));
        }

        try {
            $this->dnaService->analyze($brand, $request->user(), $customUrls);
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        }

        return Redirect::route('workspaces.brands.brand-dna.show', [$workspace, $brand])
            ->with('status', 'Website analyzed successfully. Review and approve your Brand DNA profile below.');
    }

    /**
     * Update an existing Brand DNA profile after customer edits.
     */
    public function update(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        BrandDnaProfile $brandDnaProfile,
    ): RedirectResponse {
        Gate::authorize('update', $brand);
        $this->ensureBelongsToBrand($workspace, $brand, $brandDnaProfile);

        $validated = $request->validate([
            'brand_identity' => ['nullable', 'string', 'max:2000'],
            'business_description' => ['nullable', 'string', 'max:2000'],
            'industry' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
            'primary_market' => ['nullable', 'string', 'max:100'],
            'voice_tone' => ['nullable', 'string', 'max:255'],
            'key_services' => ['nullable', 'string', 'max:2000'],
            'suggested_keywords' => ['nullable', 'string', 'max:2000'],
            'content_themes' => ['nullable', 'string', 'max:2000'],
            'brand_values' => ['nullable', 'string', 'max:2000'],
            'competitor_differentiation' => ['nullable', 'string', 'max:2000'],
        ]);

        $parseList = fn (?string $raw) => empty($raw)
            ? []
            : array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw))));

        $data = [
            'brand_identity' => $validated['brand_identity'] ?? null,
            'business_description' => $validated['business_description'] ?? null,
            'industry' => $validated['industry'] ?? null,
            'target_audience' => $validated['target_audience'] ?? null,
            'primary_market' => $validated['primary_market'] ?? null,
            'voice_tone' => $validated['voice_tone'] ?? null,
            'key_services' => $parseList($validated['key_services'] ?? null),
            'suggested_keywords' => $parseList($validated['suggested_keywords'] ?? null),
            'content_themes' => $parseList($validated['content_themes'] ?? null),
            'brand_values' => $parseList($validated['brand_values'] ?? null),
            'competitor_differentiation' => $validated['competitor_differentiation'] ?? null,
        ];

        $this->dnaService->update($brandDnaProfile, $request->user(), $data);

        return Redirect::route('workspaces.brands.brand-dna.show', [$workspace, $brand])
            ->with('status', 'Brand DNA profile updated successfully.');
    }

    /**
     * Approve Brand DNA profile and optionally apply it to the Brand's core record.
     */
    public function approve(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        BrandDnaProfile $brandDnaProfile,
    ): RedirectResponse {
        Gate::authorize('update', $brand);
        $this->ensureBelongsToBrand($workspace, $brand, $brandDnaProfile);

        $syncToBrand = $request->boolean('sync_to_brand', false);

        $this->dnaService->approve($brandDnaProfile, $request->user(), $syncToBrand);

        $message = $syncToBrand
            ? 'Brand DNA approved and successfully synced to brand core profile.'
            : 'Brand DNA approved. Brand core profile was preserved.';

        return Redirect::route('workspaces.brands.brand-dna.show', [$workspace, $brand])
            ->with('status', $message);
    }

    private function ensureBelongsToWorkspace(Workspace $workspace, Brand $brand): void
    {
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);
    }

    private function ensureBelongsToBrand(Workspace $workspace, Brand $brand, BrandDnaProfile $profile): void
    {
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $profile->brand_id === $brand->getKey()
            && $profile->workspace_id === $workspace->getKey(),
            404
        );
    }
}

