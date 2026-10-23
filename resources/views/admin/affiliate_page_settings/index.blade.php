<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .affiliate-form { display: grid; gap: 20px; }
        .affiliate-section { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .affiliate-section h4 { border-bottom: 2px solid #17a2b8; color: #17a2b8; font-size: 18px; margin: 0 0 16px; padding-bottom: 8px; }
        .affiliate-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .affiliate-field { margin-bottom: 14px; }
        .affiliate-field.full { grid-column: 1 / -1; }
        .affiliate-field label { color: #333; display: block; font-weight: 700; margin-bottom: 6px; }
        .affiliate-field input, .affiliate-field textarea, .affiliate-field select { border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; padding: 10px; width: 100%; }
        .affiliate-help { color: #6b7280; font-size: 12px; margin-top: 5px; }
        .workflow-step { background: #fff; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 16px; padding: 16px; }
        .workflow-step h5 { color: #374151; font-size: 15px; margin: 0 0 12px; }
        .affiliate-save { background: #28a745; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-size: 16px; font-weight: 700; padding: 15px 25px; width: 100%; }
        @media (max-width: 760px) { .affiliate-grid { grid-template-columns: 1fr; } .affiliate-field.full { grid-column: auto; } }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Affiliate Page Settings</h3>
                    <p class="affiliate-help">This interface controls informational landing-page content only. It cannot open applications, configure commissions, issue referral links, or enable payouts.</p>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.affiliate_page.update') }}" method="POST" class="affiliate-form">
                        @csrf

                        <section class="affiliate-section">
                            <h4>Hero</h4>
                            <div class="affiliate-grid">
                                <div class="affiliate-field"><label for="hero_badge">Badge</label><input id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $setting->hero_badge) }}"></div>
                                <div class="affiliate-field"><label for="hero_highlighted_title">Highlighted title</label><input id="hero_highlighted_title" name="hero_highlighted_title" value="{{ old('hero_highlighted_title', $setting->hero_highlighted_title) }}"></div>
                                <div class="affiliate-field full"><label for="hero_title">Main title</label><input id="hero_title" name="hero_title" value="{{ old('hero_title', $setting->hero_title) }}"></div>
                                <div class="affiliate-field full"><label for="hero_description">Description</label><textarea id="hero_description" name="hero_description" rows="3">{{ old('hero_description', $setting->hero_description) }}</textarea></div>
                                @foreach(['primary' => 'Primary CTA', 'secondary' => 'Secondary CTA'] as $prefix => $label)
                                    <div class="affiliate-field"><label for="{{ $prefix }}_cta_label">{{ $label }} label</label><input id="{{ $prefix }}_cta_label" name="{{ $prefix }}_cta_label" value="{{ old($prefix.'_cta_label', $setting->{$prefix.'_cta_label'}) }}"></div>
                                    <div class="affiliate-field"><label for="{{ $prefix }}_cta_destination">{{ $label }} destination</label><select id="{{ $prefix }}_cta_destination" name="{{ $prefix }}_cta_destination"><option value="">Use default</option>@foreach($destinationOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old($prefix.'_cta_destination', $setting->{$prefix.'_cta_destination'}) === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                                @endforeach
                                <div class="affiliate-field full"><label for="hero_disclosure">Supplemental disclosure</label><textarea id="hero_disclosure" name="hero_disclosure" rows="2">{{ old('hero_disclosure', $setting->hero_disclosure) }}</textarea><div class="affiliate-help">The fixed notice that standard registration is not affiliate enrollment is always shown.</div></div>
                            </div>
                        </section>

                        <section class="affiliate-section">
                            <h4>Program Status</h4>
                            <div class="affiliate-grid">
                                <div class="affiliate-field full"><label for="status_heading">Status heading</label><input id="status_heading" name="status_heading" value="{{ old('status_heading', $setting->status_heading) }}"></div>
                                <div class="affiliate-field full"><label for="status_description">Status explanation</label><textarea id="status_description" name="status_description" rows="3">{{ old('status_description', $setting->status_description) }}</textarea></div>
                                <div class="affiliate-field"><label for="terms_heading">Terms-card heading</label><input id="terms_heading" name="terms_heading" value="{{ old('terms_heading', $setting->terms_heading) }}"></div>
                                <div class="affiliate-field"><label for="availability_status">Availability state</label><select id="availability_status" name="availability_status" required>@foreach($availabilityOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old('availability_status', $setting->availability_status ?: 'not_open') === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                                <div class="affiliate-field full"><label for="availability_description">Availability explanation</label><textarea id="availability_description" name="availability_description" rows="3">{{ old('availability_description', $setting->availability_description) }}</textarea></div>
                            </div>
                        </section>

                        <section class="affiliate-section">
                            <h4>Proposed Three-Step Workflow</h4>
                            <div class="affiliate-grid">
                                <div class="affiliate-field full"><label for="workflow_heading">Section heading</label><input id="workflow_heading" name="workflow_heading" value="{{ old('workflow_heading', $setting->workflow_heading) }}"></div>
                                <div class="affiliate-field full"><label for="workflow_description">Section description</label><textarea id="workflow_description" name="workflow_description" rows="2">{{ old('workflow_description', $setting->workflow_description) }}</textarea></div>
                            </div>

                            @php
                                $submittedWorkflowSteps = old('workflow_steps', $setting->workflow_steps ?: []);
                                $workflowSteps = collect($defaultWorkflowSteps)
                                    ->map(fn (array $fallback, int $index): array => array_merge($fallback, $submittedWorkflowSteps[$index] ?? []))
                                    ->all();
                            @endphp
                            @foreach($workflowSteps as $index => $step)
                                <div class="workflow-step">
                                    <h5>Step {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</h5>
                                    <div class="affiliate-grid">
                                        <div class="affiliate-field"><label for="workflow_steps_{{ $index }}_icon">Icon</label><select id="workflow_steps_{{ $index }}_icon" name="workflow_steps[{{ $index }}][icon]">@foreach($workflowIcons as $icon)<option value="{{ $icon }}" @selected(($step['icon'] ?? '') === $icon)>{{ $icon }}</option>@endforeach</select></div>
                                        <div class="affiliate-field"><label for="workflow_steps_{{ $index }}_title">Title</label><input id="workflow_steps_{{ $index }}_title" name="workflow_steps[{{ $index }}][title]" value="{{ $step['title'] ?? '' }}"></div>
                                        <div class="affiliate-field full"><label for="workflow_steps_{{ $index }}_description">Description</label><textarea id="workflow_steps_{{ $index }}_description" name="workflow_steps[{{ $index }}][description]" rows="3">{{ $step['description'] ?? '' }}</textarea></div>
                                    </div>
                                </div>
                            @endforeach
                        </section>

                        <section class="affiliate-section">
                            <h4>Marketing Resources & Final Informational CTA</h4>
                            <div class="affiliate-grid">
                                <div class="affiliate-field"><label for="resources_badge">Badge</label><input id="resources_badge" name="resources_badge" value="{{ old('resources_badge', $setting->resources_badge) }}"></div>
                                <div class="affiliate-field"><label for="resources_availability_status">Availability notice</label><select id="resources_availability_status" name="resources_availability_status" required>@foreach($resourceAvailabilityOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old('resources_availability_status', $setting->resources_availability_status ?: 'not_available') === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                                <div class="affiliate-field full"><label for="resources_heading">Heading</label><input id="resources_heading" name="resources_heading" value="{{ old('resources_heading', $setting->resources_heading) }}"></div>
                                <div class="affiliate-field full"><label for="resources_description">Description</label><textarea id="resources_description" name="resources_description" rows="3">{{ old('resources_description', $setting->resources_description) }}</textarea></div>
                                <div class="affiliate-field"><label for="final_cta_label">CTA label</label><input id="final_cta_label" name="final_cta_label" value="{{ old('final_cta_label', $setting->final_cta_label) }}"></div>
                                <div class="affiliate-field"><label for="final_cta_destination">CTA destination</label><select id="final_cta_destination" name="final_cta_destination"><option value="">Use default</option>@foreach($informationalDestinationOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old('final_cta_destination', $setting->final_cta_destination) === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                            </div>
                        </section>

                        <button type="submit" class="affiliate-save">Save Affiliate Page Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
