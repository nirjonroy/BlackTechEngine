<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .input-group input, .input-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .section-title { border-bottom: 2px solid #17a2b8; padding-bottom: 5px; margin-bottom: 15px; font-size: 18px; font-weight: bold; color: #17a2b8; }
        .dynamic-item { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; position: relative; }
        .remove-btn { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Feature Page Settings</h3>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.feature_page.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            
                            <!-- Left Column: Content Settings -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                
                                <!-- Hero Section -->
                                <div class="section-title">Hero Section</div>
                                <div class="input-group"><label>Hero Badge</label><input type="text" name="hero_badge" value="{{ $setting->hero_badge }}"></div>
                                <div class="input-group"><label>Title (Normal)</label><input type="text" name="hero_title_normal" value="{{ $setting->hero_title_normal }}"></div>
                                <div class="input-group"><label>Title (Highlighted)</label><input type="text" name="hero_title_highlighted" value="{{ $setting->hero_title_highlighted }}"></div>
                                <div class="input-group"><label>Hero Description</label><textarea name="hero_description" rows="3">{{ $setting->hero_description }}</textarea></div>

                                <!-- Features Section -->
                                <div class="section-title" style="margin-top: 30px;">The 6 Pillars (Features)</div>
                                <div id="features-container">
                                    @if($setting->features_json)
                                        @foreach($setting->features_json as $index =>$feature)
                                        <div class="dynamic-item">
                                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
                                            <div class="input-group"><label>Icon (e.g. dna, calendar)</label><input type="text" name="features_json[{{ $index }}][icon]" value="{{ $feature['icon'] ?? '' }}"></div>
                                            <div class="input-group"><label>Title</label><input type="text" name="features_json[{{ $index }}][title]" value="{{ $feature['title'] ?? '' }}"></div>
                                            <div class="input-group"><label>Description</label><textarea name="features_json[{{ $index }}][description]" rows="2">{{ $feature['description'] ?? '' }}</textarea></div>
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" onclick="addFeature()" style="padding:8px 15px; background:#17a2b8; color:white; border:none; border-radius:4px; cursor:pointer;">+ Add Feature</button>

                                <!-- Calculator Section -->
                                <div class="section-title" style="margin-top: 30px;">Calculator Section</div>
                                <div class="input-group"><label>Calculator Title</label><input type="text" name="calc_title" value="{{ $setting->calc_title }}"></div>
                                <div class="input-group"><label>Calculator Description</label><textarea name="calc_description" rows="2">{{ $setting->calc_description }}</textarea></div>

                                <!-- Testimonials Section -->
                                <div class="section-title" style="margin-top: 30px;">Testimonials (Wall of Love)</div>
                                <div class="input-group"><label>Testimonial Badge</label><input type="text" name="testimonial_badge" value="{{ $setting->testimonial_badge }}"></div>
                                <div class="input-group"><label>Testimonial Title</label><input type="text" name="testimonial_title" value="{{ $setting->testimonial_title }}"></div>
                                
                                <div id="testimonials-container">
                                    @if($setting->testimonials_json)
                                        @foreach($setting->testimonials_json as $index =>$testimonial)
                                        <div class="dynamic-item">
                                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
                                            <div class="input-group"><label>Quote / Review</label><textarea name="testimonials_json[{{ $index }}][quote]" rows="3">{{ $testimonial['quote'] ?? '' }}</textarea></div>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                                <div class="input-group"><label>Initials (e.g. AL)</label><input type="text" name="testimonials_json[{{ $index }}][author_initials]" value="{{ $testimonial['author_initials'] ?? '' }}"></div>
                                                <div class="input-group"><label>Author Name</label><input type="text" name="testimonials_json[{{ $index }}][author_name]" value="{{ $testimonial['author_name'] ?? '' }}"></div>
                                                <div class="input-group"><label>Author Role</label><input type="text" name="testimonials_json[{{ $index }}][author_role]" value="{{ $testimonial['author_role'] ?? '' }}"></div>
                                            </div>
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" onclick="addTestimonial()" style="padding:8px 15px; background:#17a2b8; color:white; border:none; border-radius:4px; cursor:pointer;">+ Add Testimonial</button>

                            </div>

                            <!-- Right Column: SEO Settings -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">SEO & Meta Data</div>
                                <div class="input-group"><label>SEO Title</label><input type="text" name="seo_title" value="{{ $setting->seo_title }}"></div>
                                <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title" value="{{ $setting->meta_title }}"></div>
                                <div class="input-group"><label>Site Name</label><input type="text" name="site_name" value="{{ $setting->site_name }}"></div>
                                <div class="input-group"><label>Author</label><input type="text" name="author" value="{{ $setting->author }}"></div>
                                <div class="input-group"><label>Publisher</label><input type="text" name="publisher" value="{{ $setting->publisher }}"></div>
                                <div class="input-group"><label>SEO Description</label><textarea name="seo_description" rows="3">{{ $setting->seo_description }}</textarea></div>
                                <div class="input-group"><label>Meta Description</label><textarea name="meta_description" rows="3">{{ $setting->meta_description }}</textarea></div>
                                <div class="input-group"><label>Keywords (Comma separated)</label><textarea name="keywords" rows="2">{{ $setting->keywords }}</textarea></div>
                                <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image">
                                    @if(isset($setting->meta_image))
                                        <div style="margin-top: 10px;"><img src="{{ asset('storage/'.$setting->meta_image) }}" style="height: 60px; border-radius: 4px;"></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button type="submit" style="padding: 15px 25px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 20px; width: 100%; font-weight: bold;">Save Feature Page Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
    
    <script>
        // Feature Appender
        let featureCount = {{ $setting->features_json ? count($setting->features_json) : 0 }};
        function addFeature() {
            let html = `
            <div class="dynamic-item">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
                <div class="input-group"><label>Icon (e.g. dna, calendar)</label><input type="text" name="features_json[${featureCount}][icon]" placeholder="Icon name"></div>
                <div class="input-group"><label>Title</label><input type="text" name="features_json[${featureCount}][title]" placeholder="Feature Title"></div>
                <div class="input-group"><label>Description</label><textarea name="features_json[${featureCount}][description]" rows="2" placeholder="Feature Description"></textarea></div>
            </div>`;
            document.getElementById('features-container').insertAdjacentHTML('beforeend', html);
            featureCount++;
        }

        // Testimonial Appender
        let testimonialCount = {{ $setting->testimonials_json ? count($setting->testimonials_json) : 0 }};
        function addTestimonial() {
            let html = `
            <div class="dynamic-item">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
                <div class="input-group"><label>Quote / Review</label><textarea name="testimonials_json[${testimonialCount}][quote]" rows="3" placeholder="Customer quote..."></textarea></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="input-group"><label>Initials (e.g. AL)</label><input type="text" name="testimonials_json[${testimonialCount}][author_initials]"></div>
                    <div class="input-group"><label>Author Name</label><input type="text" name="testimonials_json[${testimonialCount}][author_name]"></div>
                    <div class="input-group"><label>Author Role</label><input type="text" name="testimonials_json[${testimonialCount}][author_role]"></div>
                </div>
            </div>`;
            document.getElementById('testimonials-container').insertAdjacentHTML('beforeend', html);
            testimonialCount++;
        }
    </script>
</body>
</html>
