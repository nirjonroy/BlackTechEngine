<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .input-group{margin-bottom:15px}.input-group label{display:block;font-weight:700;margin-bottom:5px;color:#333}.input-group input,.input-group textarea,.input-group select{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box}.form-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,1fr);gap:20px}.section-title{border-bottom:2px solid #17a2b8;padding-bottom:5px;margin:28px 0 15px;font-size:18px;font-weight:700;color:#17a2b8}.dynamic-item{background:#fff;padding:15px;border:1px solid #ddd;border-radius:6px;margin-bottom:15px;position:relative}.nested-item{background:#f7f7f7;padding:12px;border:1px solid #e2e2e2;border-radius:5px;margin:8px 0}.remove-btn{background:#dc3545;color:#fff;border:0;padding:6px 10px;border-radius:3px;cursor:pointer}.add-btn{padding:8px 12px;background:#17a2b8;color:#fff;border:0;border-radius:4px;cursor:pointer;margin:4px 4px 8px 0}.item-heading{font-weight:700;margin:0 80px 12px 0}.hint{font-size:12px;color:#666;margin:-8px 0 12px}.inline-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}@media(max-width:900px){.form-grid,.inline-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrapper">
    @include('admin.partials.sidebar')
    <div class="main-content">
        @include('admin.partials.header')
        <div class="content"><div class="card">
            <h3>Feature Page Settings</h3>
            @include('admin.partials.validation-errors')
            @if(session('success'))<div style="color:green;margin-bottom:15px;font-weight:bold">{{ session('success') }}</div>@endif

            <form action="{{ route('admin.feature_page.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div style="background:#fafafa;padding:15px;border:1px solid #eee;border-radius:5px">
                        <div class="section-title" style="margin-top:0">Hero Section</div>
                        <div class="input-group"><label for="hero_badge">Hero Badge</label><input id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $setting->hero_badge) }}"></div>
                        <div class="inline-grid"><div class="input-group"><label for="hero_title_normal">Title (Normal)</label><input id="hero_title_normal" name="hero_title_normal" value="{{ old('hero_title_normal', $setting->hero_title_normal) }}"></div><div class="input-group"><label for="hero_title_highlighted">Title (Highlighted)</label><input id="hero_title_highlighted" name="hero_title_highlighted" value="{{ old('hero_title_highlighted', $setting->hero_title_highlighted) }}"></div></div>
                        <div class="input-group"><label for="hero_description">Hero Description</label><textarea id="hero_description" name="hero_description" rows="3">{{ old('hero_description', $setting->hero_description) }}</textarea></div>

                        <div class="section-title">Feature Content</div>
                        <p class="hint">The six primary identities are fixed so each feature always keeps its intended visual layout. Additional cards can be removed.</p>
                        <div id="features-container">
                            @foreach($features as $index => $feature)
                                @php($isPrimary = in_array($feature['id'] ?? null, \App\Services\FeaturePageContent::PRIMARY_IDS, true) || $index < 6)
                                <div class="dynamic-item feature-item" data-feature-index="{{ $index }}">
                                    @unless($isPrimary)<button type="button" class="remove-btn" style="position:absolute;right:10px;top:10px" onclick="this.closest('.feature-item').remove()">Remove card</button>@endunless
                                    <div class="item-heading">{{ $isPrimary ? 'Primary feature '.($index + 1) : 'Additional feature' }}</div>
                                    <input type="hidden" name="features_json[{{ $index }}][id]" value="{{ $feature['id'] ?? '' }}">
                                    <input type="hidden" name="features_json[{{ $index }}][order]" value="{{ $feature['order'] ?? (($index + 1) * 10) }}">
                                    <div class="input-group"><label>Icon</label><select name="features_json[{{ $index }}][icon]">@foreach($iconOptions as $value => $label)<option value="{{ $value }}" @selected(($feature['icon'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="input-group"><label>Title</label><input name="features_json[{{ $index }}][title]" value="{{ $feature['title'] ?? '' }}"></div>
                                    <div class="input-group"><label>Description</label><textarea name="features_json[{{ $index }}][description]" rows="2">{{ $feature['description'] ?? '' }}</textarea></div>

                                    <details @if($errors->has("features_json.$index.*")) open @endif><summary><strong>Supporting points</strong></summary><div data-nested="supporting_points">
                                        @foreach(($feature['supporting_points'] ?? []) as $pointIndex => $point)
                                            <div class="nested-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button><div class="input-group"><label>Icon</label><select name="features_json[{{ $index }}][supporting_points][{{ $pointIndex }}][icon]">@foreach($iconOptions as $value => $label)<option value="{{ $value }}" @selected(($point['icon'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div><div class="input-group"><label>Point title</label><input name="features_json[{{ $index }}][supporting_points][{{ $pointIndex }}][title]" value="{{ $point['title'] ?? '' }}"></div><div class="input-group"><label>Point description</label><textarea name="features_json[{{ $index }}][supporting_points][{{ $pointIndex }}][description]">{{ $point['description'] ?? '' }}</textarea></div></div>
                                        @endforeach
                                    </div><button type="button" class="add-btn" onclick="addNested(this, 'supporting_points')">+ Point</button></details>

                                    @if($isPrimary && $index < 4)
                                        <details><summary><strong>Highlight cards</strong></summary><div data-nested="highlights">@foreach(($feature['highlights'] ?? []) as $highlightIndex => $highlight)<div class="nested-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button><div class="input-group"><label>Label</label><input name="features_json[{{ $index }}][highlights][{{ $highlightIndex }}][label]" value="{{ $highlight['label'] ?? '' }}"></div><div class="input-group"><label>Value</label><input name="features_json[{{ $index }}][highlights][{{ $highlightIndex }}][value]" value="{{ $highlight['value'] ?? '' }}"></div><div class="input-group"><label>Description</label><textarea name="features_json[{{ $index }}][highlights][{{ $highlightIndex }}][description]">{{ $highlight['description'] ?? '' }}</textarea></div></div>@endforeach</div><button type="button" class="add-btn" onclick="addNested(this, 'highlights')">+ Highlight</button></details>
                                        <details><summary><strong>Visual preview copy</strong></summary><div class="inline-grid"><div class="input-group"><label>Heading</label><input name="features_json[{{ $index }}][preview_heading]" value="{{ $feature['preview_heading'] ?? '' }}"></div><div class="input-group"><label>Label</label><input name="features_json[{{ $index }}][preview_label]" value="{{ $feature['preview_label'] ?? '' }}"></div><div class="input-group"><label>Badge</label><input name="features_json[{{ $index }}][preview_badge]" value="{{ $feature['preview_badge'] ?? '' }}"></div></div><div class="input-group"><label>Preview description</label><textarea name="features_json[{{ $index }}][preview_description]">{{ $feature['preview_description'] ?? '' }}</textarea></div><div data-nested="preview_items">@foreach(($feature['preview_items'] ?? []) as $previewIndex => $preview)<div class="nested-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button><div class="input-group"><label>Icon</label><select name="features_json[{{ $index }}][preview_items][{{ $previewIndex }}][icon]">@foreach($iconOptions as $value => $label)<option value="{{ $value }}" @selected(($preview['icon'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div><div class="inline-grid"><div class="input-group"><label>Label</label><input name="features_json[{{ $index }}][preview_items][{{ $previewIndex }}][label]" value="{{ $preview['label'] ?? '' }}"></div><div class="input-group"><label>Status</label><input name="features_json[{{ $index }}][preview_items][{{ $previewIndex }}][status]" value="{{ $preview['status'] ?? '' }}"></div></div><div class="input-group"><label>Description</label><textarea name="features_json[{{ $index }}][preview_items][{{ $previewIndex }}][description]">{{ $preview['description'] ?? '' }}</textarea></div></div>@endforeach</div><button type="button" class="add-btn" onclick="addNested(this, 'preview_items')">+ Preview item</button></details>
                                    @else
                                        <div class="input-group"><label>Footer label</label><input name="features_json[{{ $index }}][footer_label]" value="{{ $feature['footer_label'] ?? '' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="add-btn" onclick="addFeature()">+ Add additional feature</button>

                        <div class="section-title">Additional Features Introduction</div>
                        <div class="input-group"><label>Heading</label><input name="additional_features_heading" value="{{ old('additional_features_heading', $setting->additional_features_heading) }}"></div>
                        <div class="input-group"><label>Description</label><textarea name="additional_features_description" rows="2">{{ old('additional_features_description', $setting->additional_features_description) }}</textarea></div>

                        <div class="section-title">Calculator Introduction</div>
                        <div class="input-group"><label>Title</label><input name="calc_title" value="{{ old('calc_title', $setting->calc_title) }}"></div><div class="input-group"><label>Description</label><textarea name="calc_description">{{ old('calc_description', $setting->calc_description) }}</textarea></div><div class="input-group"><label>Disclaimer / availability label</label><textarea name="calc_disclaimer">{{ old('calc_disclaimer', $setting->calc_disclaimer) }}</textarea></div>

                        <div class="section-title">Final Call to Action</div>
                        <div class="input-group"><label>Heading</label><input name="final_cta_heading" value="{{ old('final_cta_heading', $setting->final_cta_heading) }}"></div><div class="input-group"><label>Description</label><textarea name="final_cta_description">{{ old('final_cta_description', $setting->final_cta_description) }}</textarea></div>
                        <div class="inline-grid"><div class="input-group"><label>Primary label</label><input name="final_primary_cta_label" value="{{ old('final_primary_cta_label', $setting->final_primary_cta_label) }}"></div><div class="input-group"><label>Primary destination</label><select name="final_primary_cta_destination"><option value="">Create account (default)</option>@foreach($destinationOptions as $value => $label)<option value="{{ $value }}" @selected(old('final_primary_cta_destination', $setting->final_primary_cta_destination) === $value)>{{ $label }}</option>@endforeach</select></div><div class="input-group"><label>Secondary label</label><input name="final_secondary_cta_label" value="{{ old('final_secondary_cta_label', $setting->final_secondary_cta_label) }}"></div><div class="input-group"><label>Secondary destination</label><select name="final_secondary_cta_destination"><option value="">Pricing (default)</option>@foreach($destinationOptions as $value => $label)<option value="{{ $value }}" @selected(old('final_secondary_cta_destination', $setting->final_secondary_cta_destination) === $value)>{{ $label }}</option>@endforeach</select></div></div>

                        <div class="section-title">Testimonials</div>
                        <div class="inline-grid"><div class="input-group"><label>Badge</label><input name="testimonial_badge" value="{{ old('testimonial_badge', $setting->testimonial_badge) }}"></div><div class="input-group"><label>Title</label><input name="testimonial_title" value="{{ old('testimonial_title', $setting->testimonial_title) }}"></div></div>
                        <div id="testimonials-container">@foreach(old('testimonials_json', $setting->testimonials_json ?? []) as $index => $testimonial)<div class="dynamic-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button><div class="input-group"><label>Quote</label><textarea name="testimonials_json[{{ $index }}][quote]">{{ $testimonial['quote'] ?? '' }}</textarea></div><div class="inline-grid"><div class="input-group"><label>Initials</label><input name="testimonials_json[{{ $index }}][author_initials]" value="{{ $testimonial['author_initials'] ?? '' }}"></div><div class="input-group"><label>Name</label><input name="testimonials_json[{{ $index }}][author_name]" value="{{ $testimonial['author_name'] ?? '' }}"></div></div><div class="input-group"><label>Role</label><input name="testimonials_json[{{ $index }}][author_role]" value="{{ $testimonial['author_role'] ?? '' }}"></div></div>@endforeach</div><button type="button" class="add-btn" onclick="addTestimonial()">+ Testimonial</button>
                    </div>

                    <div style="background:#fafafa;padding:15px;border:1px solid #eee;border-radius:5px">
                        <div class="section-title" style="margin-top:0">SEO & Meta Data</div>
                        @foreach(['seo_title' => 'SEO Title','meta_title' => 'Meta Title','site_name' => 'Site Name','author' => 'Author','publisher' => 'Publisher','copyright' => 'Copyright'] as $field => $label)<div class="input-group"><label>{{ $label }}</label><input name="{{ $field }}" value="{{ old($field, $setting->{$field}) }}"></div>@endforeach
                        @foreach(['seo_description' => 'SEO Description','meta_description' => 'Meta Description','keywords' => 'Keywords'] as $field => $label)<div class="input-group"><label>{{ $label }}</label><textarea name="{{ $field }}" rows="3">{{ old($field, $setting->{$field}) }}</textarea></div>@endforeach
                        <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image" accept="image/jpeg,image/png,image/webp">@if($setting->meta_image)<div style="margin-top:10px"><img src="{{ asset('storage/'.$setting->meta_image) }}" alt="Current meta image" style="height:60px;border-radius:4px"></div>@endif</div>
                    </div>
                </div>
                <button type="submit" style="padding:15px 25px;background:#28a745;color:#fff;border:0;border-radius:4px;font-size:16px;cursor:pointer;margin-top:20px;width:100%;font-weight:700">Save Feature Page Settings</button>
            </form>
        </div></div>
        @include('admin.partials.footer')
    </div>
</div>

<script>
    const iconOptions = @json($iconOptions);
    let featureCount = {{ count($features) }};
    let testimonialCount = {{ count(old('testimonials_json', $setting->testimonials_json ?? [])) }};
    const optionMarkup = (selected = 'sparkles') => Object.entries(iconOptions).map(([value, label]) => `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`).join('');

    function addFeature() {
        const index = featureCount++;
        document.getElementById('features-container').insertAdjacentHTML('beforeend', `<div class="dynamic-item feature-item" data-feature-index="${index}"><button type="button" class="remove-btn" style="position:absolute;right:10px;top:10px" onclick="this.closest('.feature-item').remove()">Remove card</button><div class="item-heading">Additional feature</div><input type="hidden" name="features_json[${index}][id]" value="additional-${Date.now()}"><input type="hidden" name="features_json[${index}][order]" value="${100 + index}"><div class="input-group"><label>Icon</label><select name="features_json[${index}][icon]">${optionMarkup()}</select></div><div class="input-group"><label>Title</label><input name="features_json[${index}][title]"></div><div class="input-group"><label>Description</label><textarea name="features_json[${index}][description]"></textarea></div><details><summary><strong>Supporting points</strong></summary><div data-nested="supporting_points"></div><button type="button" class="add-btn" onclick="addNested(this, 'supporting_points')">+ Point</button></details><div class="input-group"><label>Footer label</label><input name="features_json[${index}][footer_label]" value="Platform capability"></div></div>`);
    }

    function addNested(button, type) {
        const feature = button.closest('.feature-item');
        const featureIndex = feature.dataset.featureIndex;
        const container = feature.querySelector(`[data-nested="${type}"]`);
        const itemIndex = Date.now();
        let fields = '';
        if (type === 'supporting_points') fields = `<div class="input-group"><label>Icon</label><select name="features_json[${featureIndex}][${type}][${itemIndex}][icon]">${optionMarkup('check')}</select></div><div class="input-group"><label>Point title</label><input name="features_json[${featureIndex}][${type}][${itemIndex}][title]"></div><div class="input-group"><label>Description</label><textarea name="features_json[${featureIndex}][${type}][${itemIndex}][description]"></textarea></div>`;
        if (type === 'highlights') fields = `<div class="input-group"><label>Label</label><input name="features_json[${featureIndex}][${type}][${itemIndex}][label]"></div><div class="input-group"><label>Value</label><input name="features_json[${featureIndex}][${type}][${itemIndex}][value]"></div><div class="input-group"><label>Description</label><textarea name="features_json[${featureIndex}][${type}][${itemIndex}][description]"></textarea></div>`;
        if (type === 'preview_items') fields = `<div class="input-group"><label>Icon</label><select name="features_json[${featureIndex}][${type}][${itemIndex}][icon]">${optionMarkup('check')}</select></div><div class="input-group"><label>Label</label><input name="features_json[${featureIndex}][${type}][${itemIndex}][label]"></div><div class="input-group"><label>Status</label><input name="features_json[${featureIndex}][${type}][${itemIndex}][status]"></div><div class="input-group"><label>Description</label><textarea name="features_json[${featureIndex}][${type}][${itemIndex}][description]"></textarea></div>`;
        container.insertAdjacentHTML('beforeend', `<div class="nested-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button>${fields}</div>`);
    }

    function addTestimonial() {
        const index = testimonialCount++;
        document.getElementById('testimonials-container').insertAdjacentHTML('beforeend', `<div class="dynamic-item"><button type="button" class="remove-btn" style="float:right" onclick="this.parentElement.remove()">Remove</button><div class="input-group"><label>Quote</label><textarea name="testimonials_json[${index}][quote]"></textarea></div><div class="inline-grid"><div class="input-group"><label>Initials</label><input name="testimonials_json[${index}][author_initials]"></div><div class="input-group"><label>Name</label><input name="testimonials_json[${index}][author_name]"></div></div><div class="input-group"><label>Role</label><input name="testimonials_json[${index}][author_role]"></div></div>`);
    }
</script>
</body>
</html>
