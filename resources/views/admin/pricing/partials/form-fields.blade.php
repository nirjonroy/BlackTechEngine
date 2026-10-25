@php
    $persistedFeatures = $plan->exists
        ? $plan->features->map(fn ($feature) => [
            'name' => $feature->name,
            'is_included' => $feature->is_included,
            'order_by' => $feature->order_by,
        ])->all()
        : [['name' => '', 'is_included' => true, 'order_by' => 0]];
    $oldFeatures = old('features');
    $features = old('features_present') !== null
        ? (is_array($oldFeatures) ? $oldFeatures : [])
        : $persistedFeatures;
    $nextFeatureIndex = $features === []
        ? 0
        : max(array_map('intval', array_keys($features))) + 1;
    $inputClasses = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
    $labelClasses = 'block text-xs font-bold text-slate-700 dark:text-slate-300';
    $helpClasses = 'mt-1 text-[11px] leading-5 text-slate-500 dark:text-slate-400';
@endphp

@if($errors->any())
    <div class="flex items-start gap-3 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-700 dark:text-rose-300" role="alert" aria-labelledby="pricing-validation-heading">
        <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
        <div>
            <h2 id="pricing-validation-heading" class="font-bold">Please correct the highlighted fields.</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-7" aria-labelledby="plan-details-heading">
        <div class="flex items-start gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <i data-lucide="layers-3" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <h2 id="plan-details-heading" class="font-heading text-lg font-bold">Plan details</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Public catalog copy, prices, ordering, and availability.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="{{ $labelClasses }}">Plan name <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $plan->name) }}" class="mt-1.5 {{ $inputClasses }} @error('name') border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 @enderror" required autofocus aria-describedby="name-help @error('name') name-error @enderror">
                <p id="name-help" class="{{ $helpClasses }}">Internal and public plan identifier displayed throughout the Pricing CMS.</p>
                @error('name')<p id="name-error" class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="badge" class="{{ $labelClasses }}">Badge</label>
                <input id="badge" name="badge" type="text" maxlength="255" value="{{ old('badge', $plan->badge) }}" class="mt-1.5 {{ $inputClasses }} @error('badge') border-rose-500 @enderror" placeholder="For teams">
                @error('badge')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="title" class="{{ $labelClasses }}">Display title</label>
                <input id="title" name="title" type="text" maxlength="255" value="{{ old('title', $plan->title) }}" class="mt-1.5 {{ $inputClasses }} @error('title') border-rose-500 @enderror" placeholder="Authority Engine">
                @error('title')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="{{ $labelClasses }}">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="5000" class="mt-1.5 {{ $inputClasses }} resize-y @error('description') border-rose-500 @enderror" placeholder="Describe who this marketing plan is intended for.">{{ old('description', $plan->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="monthly_price" class="{{ $labelClasses }}">Monthly price <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input id="monthly_price" name="monthly_price" type="number" inputmode="decimal" min="0" max="999999.99" step="0.01" value="{{ old('monthly_price', $plan->monthly_price ?? '0.00') }}" class="mt-1.5 {{ $inputClasses }} font-mono @error('monthly_price') border-rose-500 @enderror" required>
                <p class="{{ $helpClasses }}">Up to two decimal places.</p>
                @error('monthly_price')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="annual_price" class="{{ $labelClasses }}">Annual total <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input id="annual_price" name="annual_price" type="number" inputmode="decimal" min="0" max="999999.99" step="0.01" value="{{ old('annual_price', $plan->annual_price ?? '0.00') }}" class="mt-1.5 {{ $inputClasses }} font-mono @error('annual_price') border-rose-500 @enderror" required>
                <p class="{{ $helpClasses }}">Stored as the complete annual amount.</p>
                @error('annual_price')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_order" class="{{ $labelClasses }}">Display order <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input id="display_order" name="display_order" type="number" inputmode="numeric" min="0" max="10000" step="1" value="{{ old('display_order', $plan->display_order ?? 0) }}" class="mt-1.5 {{ $inputClasses }} font-mono @error('display_order') border-rose-500 @enderror" required>
                <p class="{{ $helpClasses }}">Lower values appear first.</p>
                @error('display_order')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="popular_label" class="{{ $labelClasses }}">Popular label</label>
                <input id="popular_label" name="popular_label" type="text" maxlength="255" value="{{ old('popular_label', $plan->popular_label) }}" class="mt-1.5 {{ $inputClasses }} @error('popular_label') border-rose-500 @enderror" placeholder="Most Popular">
                @error('popular_label')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-cyan-500 focus-within:ring-2 focus-within:ring-cyan-500 dark:border-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked((bool) old('is_active', $plan->exists ? $plan->is_active : true))>
                    <span>
                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Publicly visible</span>
                        <span class="mt-1 block text-[11px] leading-5 text-slate-500 dark:text-slate-400">Active plans may appear on the public Pricing page.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500 dark:border-slate-700">
                    <input type="hidden" name="is_popular" value="0">
                    <input type="checkbox" name="is_popular" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked((bool) old('is_popular', $plan->is_popular))>
                    <span>
                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Popular plan</span>
                        <span class="mt-1 block text-[11px] leading-5 text-slate-500 dark:text-slate-400">Highlights this tier using the configured popular label.</span>
                    </span>
                </label>
            </div>

            <div>
                <label for="button_text" class="{{ $labelClasses }}">CTA label</label>
                <input id="button_text" name="button_text" type="text" maxlength="255" value="{{ old('button_text', $plan->button_text) }}" class="mt-1.5 {{ $inputClasses }} @error('button_text') border-rose-500 @enderror" placeholder="Create account">
                @error('button_text')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="button_link" class="{{ $labelClasses }}">Approved CTA destination</label>
                <select id="button_link" name="button_link" class="mt-1.5 {{ $inputClasses }} @error('button_link') border-rose-500 @enderror">
                    <option value="">Unavailable / no destination</option>
                    @foreach($destinationOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('button_link', $plan->button_link) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="{{ $helpClasses }}">Only approved internal named routes are accepted. This does not initiate checkout.</p>
                @error('button_link')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="seo-settings-heading">
        <div class="flex items-start gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                <i data-lucide="search-check" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <h2 id="seo-settings-heading" class="font-heading text-lg font-bold">Plan SEO metadata</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Optional metadata already supported by the Pricing CMS.</p>
            </div>
        </div>

        <div class="mt-6 space-y-5">
            @foreach([
                'seo_title' => ['label' => 'SEO title', 'max' => 1000],
                'meta_title' => ['label' => 'Meta title', 'max' => 255],
                'author' => ['label' => 'Author', 'max' => 255],
                'publisher' => ['label' => 'Publisher', 'max' => 255],
                'copyright' => ['label' => 'Copyright', 'max' => 255],
                'site_name' => ['label' => 'Site name', 'max' => 255],
            ] as $field => $configuration)
                <div>
                    <label for="{{ $field }}" class="{{ $labelClasses }}">{{ $configuration['label'] }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="{{ $configuration['max'] }}" value="{{ old($field, $plan->{$field}) }}" class="mt-1.5 {{ $inputClasses }} @error($field) border-rose-500 @enderror">
                    @error($field)<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endforeach

            @foreach([
                'keywords' => ['label' => 'Keywords', 'max' => 5000],
                'seo_description' => ['label' => 'SEO description', 'max' => 5000],
                'meta_description' => ['label' => 'Meta description', 'max' => 5000],
            ] as $field => $configuration)
                <div>
                    <label for="{{ $field }}" class="{{ $labelClasses }}">{{ $configuration['label'] }}</label>
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="{{ $configuration['max'] }}" class="mt-1.5 {{ $inputClasses }} resize-y @error($field) border-rose-500 @enderror">{{ old($field, $plan->{$field}) }}</textarea>
                    @error($field)<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div>
                <label for="meta_image" class="{{ $labelClasses }}">Meta image</label>
                <input id="meta_image" type="file" name="meta_image" accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white text-xs text-slate-600 file:mr-3 file:border-0 file:bg-indigo-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:file:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                <p class="{{ $helpClasses }}">JPEG, PNG, or WebP; maximum 5 MB and 6000&times;6000 pixels.</p>
                @error('meta_image')<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                @if($plan->meta_image)
                    <div class="mt-3 rounded-2xl border border-slate-200 p-3 dark:border-slate-700">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Current image</p>
                        <img src="{{ asset('storage/'.$plan->meta_image) }}" alt="Current meta image for {{ $plan->name }}" class="max-h-32 max-w-full rounded-xl object-contain">
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-12" aria-labelledby="plan-features-heading">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="list-checks" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 id="plan-features-heading" class="font-heading text-lg font-bold">Ordered plan features</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Included and excluded marketing features appear in ascending order.</p>
                </div>
            </div>
            <button id="add-pricing-feature" type="button" class="inline-flex w-fit items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                Add feature
            </button>
        </div>

        <input type="hidden" name="features_present" value="1">
        <div class="mt-5 hidden grid-cols-[minmax(0,1fr)_7rem_auto_auto] gap-3 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 sm:grid" aria-hidden="true">
            <span>Feature name</span>
            <span>Order</span>
            <span>Availability</span>
            <span>Action</span>
        </div>
        <div id="features-container" class="mt-3 space-y-3" data-next-index="{{ $nextFeatureIndex }}">
            @foreach($features as $index => $feature)
                <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-[minmax(0,1fr)_7rem_auto_auto] sm:items-start" data-pricing-feature-row>
                    <div>
                        <label for="feature-{{ $index }}-name" class="{{ $labelClasses }} sm:sr-only">Feature name</label>
                        <input id="feature-{{ $index }}-name" name="features[{{ $index }}][name]" type="text" maxlength="255" value="{{ $feature['name'] ?? '' }}" placeholder="Feature name" class="mt-1.5 {{ $inputClasses }} sm:mt-0 @error("features.$index.name") border-rose-500 @enderror" required>
                        @error("features.$index.name")<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="feature-{{ $index }}-order" class="{{ $labelClasses }} sm:sr-only">Feature order</label>
                        <input id="feature-{{ $index }}-order" type="number" inputmode="numeric" min="0" max="10000" step="1" name="features[{{ $index }}][order_by]" value="{{ $feature['order_by'] ?? $index }}" class="mt-1.5 {{ $inputClasses }} font-mono sm:mt-0 @error("features.$index.order_by") border-rose-500 @enderror">
                        @error("features.$index.order_by")<p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 focus-within:ring-2 focus-within:ring-emerald-500 dark:border-slate-700 dark:text-slate-300">
                        <input type="hidden" name="features[{{ $index }}][is_included]" value="0">
                        <input type="checkbox" name="features[{{ $index }}][is_included]" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked((bool) ($feature['is_included'] ?? false))>
                        Included
                    </label>
                    <button type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-rose-500/30 px-3 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-400" data-remove-pricing-feature aria-label="Remove feature {{ $index + 1 }}">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        <span class="sm:sr-only">Remove</span>
                    </button>
                </div>
            @endforeach
        </div>

        <template id="pricing-feature-template">
            <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-[minmax(0,1fr)_7rem_auto_auto] sm:items-start" data-pricing-feature-row>
                <div>
                    <label for="feature-__INDEX__-name" class="{{ $labelClasses }} sm:sr-only">Feature name</label>
                    <input id="feature-__INDEX__-name" name="features[__INDEX__][name]" type="text" maxlength="255" placeholder="Feature name" class="mt-1.5 {{ $inputClasses }} sm:mt-0" required>
                </div>
                <div>
                    <label for="feature-__INDEX__-order" class="{{ $labelClasses }} sm:sr-only">Feature order</label>
                    <input id="feature-__INDEX__-order" type="number" inputmode="numeric" min="0" max="10000" step="1" name="features[__INDEX__][order_by]" value="__INDEX__" class="mt-1.5 {{ $inputClasses }} font-mono sm:mt-0">
                </div>
                <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 focus-within:ring-2 focus-within:ring-emerald-500 dark:border-slate-700 dark:text-slate-300">
                    <input type="hidden" name="features[__INDEX__][is_included]" value="0">
                    <input type="checkbox" name="features[__INDEX__][is_included]" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" checked>
                    Included
                </label>
                <button type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-rose-500/30 px-3 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-400" data-remove-pricing-feature aria-label="Remove new feature">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span class="sm:sr-only">Remove</span>
                </button>
            </div>
        </template>
    </section>
</div>

<div class="bte-card flex flex-col-reverse gap-3 rounded-3xl border border-slate-200 p-4 shadow-xl dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:p-5">
    <p class="text-[11px] leading-5 text-slate-500 dark:text-slate-400">Saving updates the marketing Pricing CMS only. IDs and timestamps are managed by the application.</p>
    <div class="flex flex-col-reverse gap-2 sm:flex-row">
        <a href="{{ route('admin.pricing.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">Cancel</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-5 py-2.5 text-xs font-black text-white shadow-md shadow-indigo-500/20 transition hover:from-cyan-400 hover:to-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
            {{ $submitLabel }}
        </button>
    </div>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('features-container');
            const addButton = document.getElementById('add-pricing-feature');
            const template = document.getElementById('pricing-feature-template');

            if (!container || !addButton || !template) {
                return;
            }

            let nextIndex = Number.parseInt(container.dataset.nextIndex ?? '0', 10);

            addButton.addEventListener('click', () => {
                const markup = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
                container.insertAdjacentHTML('beforeend', markup);
                document.getElementById(`feature-${nextIndex}-name`)?.focus();
                nextIndex += 1;
                window.lucide?.createIcons();
            });

            container.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-pricing-feature]');

                if (removeButton) {
                    removeButton.closest('[data-pricing-feature-row]')?.remove();
                    addButton.focus();
                }
            });
        });
    </script>
@endPushOnce
