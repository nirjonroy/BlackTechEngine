<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .input-group input, .input-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .section-title { border-bottom: 2px solid #17a2b8; padding-bottom: 5px; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
        .faq-item { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Pricing Page Settings (Global)</h3>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.pricing_page.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <!-- Left Column: Content Settings -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">Hero Section</div>
                                <div class="input-group"><label>Badge Icon (e.g. tag)</label><input type="text" name="hero_badge_icon" value="{{ $setting->hero_badge_icon }}"></div>
                                <div class="input-group"><label>Badge Text</label><input type="text" name="hero_badge_text" value="{{ $setting->hero_badge_text }}"></div>
                                <div class="input-group"><label>Title (Normal Part)</label><input type="text" name="hero_title_normal" value="{{ $setting->hero_title_normal }}"></div>
                                <div class="input-group"><label>Title (Highlighted Part)</label><input type="text" name="hero_title_highlighted" value="{{ $setting->hero_title_highlighted }}"></div>
                                <div class="input-group"><label>Description</label><textarea name="hero_description" rows="3">{{ $setting->hero_description }}</textarea></div>

                                <div class="section-title" style="margin-top:20px;">Calculator Section</div>
                                <div class="input-group"><label>Title</label><input type="text" name="calc_title" value="{{ $setting->calc_title }}"></div>
                                <div class="input-group"><label>Description</label><textarea name="calc_description" rows="2">{{ $setting->calc_description }}</textarea></div>

                                <div class="section-title" style="margin-top:20px;">FAQ Section</div>
                                <div class="input-group"><label>Title</label><input type="text" name="faq_title" value="{{ $setting->faq_title }}"></div>
                                <div class="input-group"><label>Description</label><textarea name="faq_description" rows="2">{{ $setting->faq_description }}</textarea></div>
                                
                                <label style="font-weight:bold;">FAQs</label>
                                <div id="faq-container">
                                    @if($setting->faqs)
                                        @foreach($setting->faqs as $index =>$faq)
                                        <div class="faq-item">
                                            <div class="input-group"><input type="text" name="faqs[{{ $index }}][question]" value="{{ $faq['question'] }}" placeholder="Question"></div>
                                            <div class="input-group"><textarea name="faqs[{{ $index }}][answer]" rows="3" placeholder="Answer">{{ $faq['answer'] }}</textarea></div>
                                            <button type="button" onclick="this.parentElement.remove()" style="background:#dc3545; color:white; border:none; padding:5px 10px;">Remove</button>
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" onclick="addFAQ()" style="padding:8px 15px; background:#17a2b8; color:white; border:none; margin-top:10px;">+ Add New FAQ</button>
                            </div>

                            <!-- Right Column: SEO Settings -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">SEO Data</div>
                                <div class="input-group"><label>SEO Title</label><input type="text" name="seo_title" value="{{ $setting->seo_title }}"></div>
                                <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title" value="{{ $setting->meta_title }}"></div>
                                <div class="input-group"><label>Site Name</label><input type="text" name="site_name" value="{{ $setting->site_name }}"></div>
                                <div class="input-group"><label>Author</label><input type="text" name="author" value="{{ $setting->author }}"></div>
                                <div class="input-group"><label>Publisher</label><input type="text" name="publisher" value="{{ $setting->publisher }}"></div>
                                <div class="input-group"><label>SEO Description</label><textarea name="seo_description" rows="3">{{ $setting->seo_description }}</textarea></div>
                                <div class="input-group"><label>Meta Description</label><textarea name="meta_description" rows="3">{{ $setting->meta_description }}</textarea></div>
                                <div class="input-group"><label>Keywords</label><textarea name="keywords" rows="2">{{ $setting->keywords }}</textarea></div>
                                <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image">
                                    @if(isset($setting->meta_image))
                                        <div style="margin-top: 10px;"><img src="{{ asset('storage/'.$setting->meta_image) }}" height="60"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <button type="submit" style="padding: 12px 25px; background: #28a745; color: white; border: none; font-size: 16px; margin-top: 15px; width: 100%;">Save Global Pricing Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
    
    <script>
        let faqCount = {{ $setting->faqs ? count($setting->faqs) : 0 }};
        function addFAQ() {
            let html = `
            <div class="faq-item">
                <div class="input-group"><input type="text" name="faqs[${faqCount}][question]" placeholder="Question"></div>
                <div class="input-group"><textarea name="faqs[${faqCount}][answer]" rows="3" placeholder="Answer"></textarea></div>
                <button type="button" onclick="this.parentElement.remove()" style="background:#dc3545; color:white; border:none; padding:5px 10px;">Remove</button>
            </div>`;
            document.getElementById('faq-container').insertAdjacentHTML('beforeend', html);
            faqCount++;
        }
    </script>
</body>
</html>
