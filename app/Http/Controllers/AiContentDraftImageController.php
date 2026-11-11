<?php

namespace App\Http\Controllers;

use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\Ai\Exceptions\AiContentGenerationException;
use App\Services\Ai\Images\AiImageGenerationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class AiContentDraftImageController extends Controller
{
    public function __construct(
        private readonly AiImageGenerationManager $imageManager,
    ) {}

    /**
     * Generate or regenerate a featured image for the draft.
     */
    public function generate(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): RedirectResponse {
        Gate::authorize('update', $aiContentDraft);
        $this->ensureBelongsToTenant($workspace, $brand, $aiContentDraft);

        $validated = $request->validate([
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->imageManager->generate(
                $request->user(),
                $workspace,
                $brand,
                $aiContentDraft,
                $validated['instructions'] ?? null,
            );
        } catch (AiContentGenerationException $e) {
            $key = $e->category === 'feature_disabled' ? 'feature' : 'generation';

            return Redirect::back()->withErrors([$key => $e->getMessage()])->withInput();
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        }

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $aiContentDraft])
            ->with('status', 'AI featured image generated successfully.');
    }

    /**
     * Update the alt text of the active featured image.
     */
    public function updateAltText(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): RedirectResponse {
        Gate::authorize('update', $aiContentDraft);
        $this->ensureBelongsToTenant($workspace, $brand, $aiContentDraft);

        $validated = $request->validate([
            'alt_text' => ['required', 'string', 'max:255'],
        ]);

        $featuredImage = $aiContentDraft->featuredImage;
        abort_if($featuredImage === null, 404, 'No active featured image found for this draft.');

        $featuredImage->update([
            'alt_text' => $validated['alt_text'],
        ]);

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $aiContentDraft])
            ->with('status', 'Featured image alt text updated successfully.');
    }

    /**
     * Remove the active featured image from the draft.
     */
    public function destroy(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): RedirectResponse {
        Gate::authorize('update', $aiContentDraft);
        $this->ensureBelongsToTenant($workspace, $brand, $aiContentDraft);

        $featuredImage = $aiContentDraft->featuredImage;
        abort_if($featuredImage === null, 404, 'No active featured image found for this draft.');

        $featuredImage->update([
            'is_active' => false,
        ]);

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $aiContentDraft])
            ->with('status', 'Featured image removed.');
    }

    private function ensureBelongsToTenant(Workspace $workspace, Brand $brand, AiContentDraft $draft): void
    {
        abort_unless(
            $draft->workspace_id === $workspace->getKey()
            && $draft->brand_id === $brand->getKey()
            && $brand->workspace_id === $workspace->getKey(),
            404
        );
    }
}
