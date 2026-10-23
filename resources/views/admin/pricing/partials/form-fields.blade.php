@php
    $features = old('features', $plan->exists
        ? $plan->features->map(fn ($feature) => ['name' => $feature->name, 'is_included' => $feature->is_included, 'order_by' => $feature->order_by])->all()
        : [['name' => '', 'is_included' => true, 'order_by' => 0]]);
@endphp
<div class="form-grid">
    <div style="background:#fafafa;padding:15px;border-radius:5px;border:1px solid #eee">
        <div class="section-title">Plan Details</div>
        <div class="input-group"><label for="name">Plan Name</label><input id="name" name="name" value="{{ old('name', $plan->name) }}" required></div>
        <div class="input-group"><label for="badge">Badge</label><input id="badge" name="badge" value="{{ old('badge', $plan->badge) }}"></div>
        <div class="input-group"><label for="title">Display Title</label><input id="title" name="title" value="{{ old('title', $plan->title) }}"></div>
        <div class="input-group"><label for="description">Description</label><textarea id="description" name="description" rows="3">{{ old('description', $plan->description) }}</textarea></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px">
            <div class="input-group"><label for="monthly_price">Monthly Price</label><input id="monthly_price" type="number" min="0" max="999999.99" step="0.01" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price ?? '0.00') }}" required></div>
            <div class="input-group"><label for="annual_price">Annual Total</label><input id="annual_price" type="number" min="0" max="999999.99" step="0.01" name="annual_price" value="{{ old('annual_price', $plan->annual_price ?? '0.00') }}" required></div>
            <div class="input-group"><label for="display_order">Display Order</label><input id="display_order" type="number" min="0" max="10000" name="display_order" value="{{ old('display_order', $plan->display_order ?? 0) }}" required></div>
        </div>
        <div class="input-group check-row">
            <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $plan->exists ? $plan->is_active : true))> Publicly visible</label>
            <label><input type="hidden" name="is_popular" value="0"><input type="checkbox" name="is_popular" value="1" @checked((bool) old('is_popular', $plan->is_popular))> Recommended plan</label>
        </div>
        <div class="input-group"><label for="popular_label">Recommended Label</label><input id="popular_label" name="popular_label" value="{{ old('popular_label', $plan->popular_label) }}" placeholder="Most Popular"></div>
        <div class="input-group"><label for="button_text">CTA Label</label><input id="button_text" name="button_text" value="{{ old('button_text', $plan->button_text) }}"></div>
        <div class="input-group"><label for="button_link">Approved CTA Destination</label><select id="button_link" name="button_link"><option value="">Unavailable / no destination</option>@foreach($destinationOptions as $value => $label)<option value="{{ $value }}" @selected(old('button_link', $plan->button_link) === $value)>{{ $label }}</option>@endforeach</select></div>
        <p style="font-size:12px;color:#666">CTA destinations use internal named routes only. This does not initiate billing or checkout.</p>

        <div class="section-title" style="margin-top:24px">Features List</div>
        <input type="hidden" name="features_present" value="1">
        <div id="features-container">
            @foreach($features as $index => $feature)
                <div class="feature-row"><input name="features[{{ $index }}][name]" value="{{ $feature['name'] ?? '' }}" placeholder="Feature name"><input type="number" min="0" max="10000" name="features[{{ $index }}][order_by]" value="{{ $feature['order_by'] ?? $index }}" aria-label="Feature order"><label><input type="hidden" name="features[{{ $index }}][is_included]" value="0"><input type="checkbox" name="features[{{ $index }}][is_included]" value="1" @checked((bool) ($feature['is_included'] ?? false))> Included</label><button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button></div>
            @endforeach
        </div>
        <button type="button" class="add-btn" onclick="addPricingFeature()">+ Add Feature</button>
    </div>

    <div style="background:#fafafa;padding:15px;border-radius:5px;border:1px solid #eee">
        <div class="section-title">SEO Settings</div>
        @foreach(['seo_title' => 'SEO Title','meta_title' => 'Meta Title','author' => 'Author','publisher' => 'Publisher','copyright' => 'Copyright','site_name' => 'Site Name'] as $field => $label)<div class="input-group"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $plan->{$field}) }}"></div>@endforeach
        @foreach(['keywords' => 'Keywords','seo_description' => 'SEO Description','meta_description' => 'Meta Description'] as $field => $label)<div class="input-group"><label for="{{ $field }}">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" rows="3">{{ old($field, $plan->{$field}) }}</textarea></div>@endforeach
        <div class="input-group"><label for="meta_image">Meta Image</label><input id="meta_image" type="file" name="meta_image" accept="image/jpeg,image/png,image/webp">@if($plan->meta_image)<img src="{{ asset('storage/'.$plan->meta_image) }}" alt="Current meta image" style="height:50px;margin-top:10px">@endif</div>
    </div>
</div>
<button type="submit" class="submit-btn">{{ $submitLabel }}</button>
<script>
    let pricingFeatureCount = {{ count($features) }};
    function addPricingFeature() {
        const index = pricingFeatureCount++;
        document.getElementById('features-container').insertAdjacentHTML('beforeend', `<div class="feature-row"><input name="features[${index}][name]" placeholder="Feature name"><input type="number" min="0" max="10000" name="features[${index}][order_by]" value="${index}" aria-label="Feature order"><label><input type="hidden" name="features[${index}][is_included]" value="0"><input type="checkbox" name="features[${index}][is_included]" value="1" checked> Included</label><button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button></div>`);
    }
</script>
