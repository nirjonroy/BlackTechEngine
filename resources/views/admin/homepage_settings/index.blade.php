<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .homepage-form { display: grid; gap: 20px; }
        .homepage-section { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .homepage-section h4 { color: #17a2b8; font-size: 18px; margin: 0 0 16px; padding-bottom: 8px; border-bottom: 2px solid #17a2b8; }
        .homepage-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .homepage-field { margin-bottom: 14px; }
        .homepage-field.full { grid-column: 1 / -1; }
        .homepage-field label { color: #333; display: block; font-weight: 700; margin-bottom: 6px; }
        .homepage-field input, .homepage-field textarea, .homepage-field select { border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; padding: 10px; width: 100%; }
        .homepage-help { color: #6b7280; font-size: 12px; margin-top: 5px; }
        .homepage-save { background: #28a745; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-size: 16px; font-weight: 700; padding: 15px 25px; width: 100%; }
        @media (max-width: 760px) { .homepage-grid { grid-template-columns: 1fr; } .homepage-field.full { grid-column: auto; } }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Homepage Settings</h3>
                    <p class="homepage-help">Blank fields use the safe public-site defaults. CTA destinations are restricted to approved internal pages.</p>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.homepage.update') }}" method="POST" class="homepage-form">
                        @csrf

                        <section class="homepage-section">
                            <h4>Hero</h4>
                            <div class="homepage-grid">
                                <div class="homepage-field"><label for="hero_badge">Badge</label><input id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $setting->hero_badge) }}"></div>
                                <div class="homepage-field"><label for="hero_highlighted_title">Highlighted title</label><input id="hero_highlighted_title" name="hero_highlighted_title" value="{{ old('hero_highlighted_title', $setting->hero_highlighted_title) }}"></div>
                                <div class="homepage-field full"><label for="hero_title">Main title</label><input id="hero_title" name="hero_title" value="{{ old('hero_title', $setting->hero_title) }}"></div>
                                <div class="homepage-field full"><label for="hero_description">Description</label><textarea id="hero_description" name="hero_description" rows="3">{{ old('hero_description', $setting->hero_description) }}</textarea></div>
                                @foreach(['primary' => 'Primary CTA', 'secondary' => 'Secondary CTA'] as $prefix => $label)
                                    <div class="homepage-field"><label for="{{ $prefix }}_cta_label">{{ $label }} label</label><input id="{{ $prefix }}_cta_label" name="{{ $prefix }}_cta_label" value="{{ old($prefix.'_cta_label', $setting->{$prefix.'_cta_label'}) }}"></div>
                                    <div class="homepage-field"><label for="{{ $prefix }}_cta_destination">{{ $label }} destination</label><select id="{{ $prefix }}_cta_destination" name="{{ $prefix }}_cta_destination"><option value="">Use default</option>@foreach($destinationOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old($prefix.'_cta_destination', $setting->{$prefix.'_cta_destination'}) === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                                @endforeach
                            </div>
                        </section>

                        <section class="homepage-section">
                            <h4>Website Scanner</h4>
                            <div class="homepage-grid">
                                <div class="homepage-field full"><label for="scanner_heading">Heading</label><input id="scanner_heading" name="scanner_heading" value="{{ old('scanner_heading', $setting->scanner_heading) }}"></div>
                                <div class="homepage-field full"><label for="scanner_description">Explanatory text</label><textarea id="scanner_description" name="scanner_description" rows="3">{{ old('scanner_description', $setting->scanner_description) }}</textarea></div>
                                <div class="homepage-field"><label for="scanner_button_label">Button label</label><input id="scanner_button_label" name="scanner_button_label" value="{{ old('scanner_button_label', $setting->scanner_button_label) }}"></div>
                            </div>
                        </section>

                        <section class="homepage-section">
                            <h4>Integrations & Features</h4>
                            <div class="homepage-grid">
                                <div class="homepage-field"><label for="integrations_heading">Integration heading</label><input id="integrations_heading" name="integrations_heading" value="{{ old('integrations_heading', $setting->integrations_heading) }}"></div>
                                <div class="homepage-field"><label for="features_badge">Features badge</label><input id="features_badge" name="features_badge" value="{{ old('features_badge', $setting->features_badge) }}"></div>
                                <div class="homepage-field full"><label for="integrations_description">Integration description</label><textarea id="integrations_description" name="integrations_description" rows="2">{{ old('integrations_description', $setting->integrations_description) }}</textarea></div>
                                <div class="homepage-field full"><label for="features_heading">Features heading</label><input id="features_heading" name="features_heading" value="{{ old('features_heading', $setting->features_heading) }}"></div>
                                <div class="homepage-field full"><label for="features_description">Features description</label><textarea id="features_description" name="features_description" rows="2">{{ old('features_description', $setting->features_description) }}</textarea></div>
                            </div>
                        </section>

                        <section class="homepage-section">
                            <h4>Calculator</h4>
                            <div class="homepage-grid">
                                <div class="homepage-field"><label for="calculator_badge">Badge</label><input id="calculator_badge" name="calculator_badge" value="{{ old('calculator_badge', $setting->calculator_badge) }}"></div>
                                <div class="homepage-field"><label for="calculator_heading">Heading</label><input id="calculator_heading" name="calculator_heading" value="{{ old('calculator_heading', $setting->calculator_heading) }}"></div>
                                <div class="homepage-field full"><label for="calculator_description">Description</label><textarea id="calculator_description" name="calculator_description" rows="3">{{ old('calculator_description', $setting->calculator_description) }}</textarea></div>
                                <div class="homepage-field full"><label for="calculator_note">Estimate note</label><textarea id="calculator_note" name="calculator_note" rows="3">{{ old('calculator_note', $setting->calculator_note) }}</textarea></div>
                                <div class="homepage-field"><label for="calculator_cta_label">CTA label</label><input id="calculator_cta_label" name="calculator_cta_label" value="{{ old('calculator_cta_label', $setting->calculator_cta_label) }}"></div>
                                <div class="homepage-field"><label for="calculator_cta_destination">CTA destination</label><select id="calculator_cta_destination" name="calculator_cta_destination"><option value="">Use default</option>@foreach($destinationOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old('calculator_cta_destination', $setting->calculator_cta_destination) === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                            </div>
                        </section>

                        <section class="homepage-section">
                            <h4>Final CTA</h4>
                            <div class="homepage-grid">
                                <div class="homepage-field full"><label for="final_cta_heading">Heading</label><input id="final_cta_heading" name="final_cta_heading" value="{{ old('final_cta_heading', $setting->final_cta_heading) }}"></div>
                                <div class="homepage-field full"><label for="final_cta_description">Description</label><textarea id="final_cta_description" name="final_cta_description" rows="3">{{ old('final_cta_description', $setting->final_cta_description) }}</textarea></div>
                                @foreach(['final_primary' => 'Primary CTA', 'final_secondary' => 'Secondary CTA'] as $prefix => $label)
                                    <div class="homepage-field"><label for="{{ $prefix }}_cta_label">{{ $label }} label</label><input id="{{ $prefix }}_cta_label" name="{{ $prefix }}_cta_label" value="{{ old($prefix.'_cta_label', $setting->{$prefix.'_cta_label'}) }}"></div>
                                    <div class="homepage-field"><label for="{{ $prefix }}_cta_destination">{{ $label }} destination</label><select id="{{ $prefix }}_cta_destination" name="{{ $prefix }}_cta_destination"><option value="">Use default</option>@foreach($destinationOptions as $value => $optionLabel)<option value="{{ $value }}" @selected(old($prefix.'_cta_destination', $setting->{$prefix.'_cta_destination'}) === $value)>{{ $optionLabel }}</option>@endforeach</select></div>
                                @endforeach
                            </div>
                        </section>

                        <button type="submit" class="homepage-save">Save Homepage Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
