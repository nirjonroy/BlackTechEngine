<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAboutPageRequest;
use App\Models\AboutPage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    public function index(): View
    {
        $about = AboutPage::with(['stats', 'comparisons', 'architectures', 'coreValues'])->first() ?? new AboutPage;

        return view('admin.about.index', compact('about'));
    }

    public function update(UpdateAboutPageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $about = AboutPage::first() ?? new AboutPage;
            $about->fill(Arr::only($validated, $about->getFillable()));
            $about->save();

            if ($this->collectionWasSubmitted($validated, 'stats')) {
                $this->syncCollection($about->stats(), $validated['stats'] ?? [], 'value');
            }

            if ($this->collectionWasSubmitted($validated, 'comparisons')) {
                $this->syncCollection($about->comparisons(), $validated['comparisons'] ?? [], 'capability');
            }

            if ($this->collectionWasSubmitted($validated, 'architectures')) {
                $this->syncCollection($about->architectures(), $validated['architectures'] ?? [], 'title');
            }

            if ($this->collectionWasSubmitted($validated, 'core_values')) {
                $this->syncCollection($about->coreValues(), $validated['core_values'] ?? [], 'title');
            }
        });

        return back()->with('success', 'About Page settings updated successfully!');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function collectionWasSubmitted(array $validated, string $key): bool
    {
        return array_key_exists($key, $validated) || ($validated[$key.'_submitted'] ?? false);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncCollection(HasMany $relationship, array $items, string $requiredField): void
    {
        $items = array_values(array_filter(
            $items,
            fn (array $item): bool => filled($item[$requiredField] ?? null),
        ));
        $submittedIds = array_values(array_filter(array_column($items, 'id')));

        if ($submittedIds === []) {
            (clone $relationship)->delete();
        } else {
            (clone $relationship)->whereNotIn('id', $submittedIds)->delete();
        }

        foreach ($items as $index => $item) {
            $attributes = [...Arr::except($item, 'id'), 'order_by' => $index];

            if (isset($item['id'])) {
                (clone $relationship)->whereKey($item['id'])->firstOrFail()->update($attributes);

                continue;
            }

            $relationship->create($attributes);
        }
    }
}
