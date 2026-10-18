<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>.input-group{margin-bottom:15px;} .input-group label{display:block;font-weight:bold;margin-bottom:5px;} .input-group input[type="text"],.input-group input[type="number"],.input-group textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;} .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;} .section-title{border-bottom:2px solid #17a2b8; padding-bottom:5px; margin-bottom:15px; font-size:18px; font-weight:bold;} .feature-row{display:flex; gap:10px; margin-bottom:10px; align-items:center;}</style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Create Pricing Plan</h3>
                    @include('admin.partials.validation-errors')
                    <form action="{{ route('admin.pricing.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <!-- Left: Plan Data & Features -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">Plan Details</div>
                                <div class="input-group"><label>Plan Name (e.g. Growth)</label><input type="text" name="name" required></div>
                                <div class="input-group"><label>Badge (e.g. Scale SaaS)</label><input type="text" name="badge"></div>
                                <div class="input-group"><label>Title (e.g. Authority Engine)</label><input type="text" name="title"></div>
                                <div class="input-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                                <div style="display:flex; gap:10px;">
                                    <div class="input-group" style="flex:1"><label>Monthly Price ($)</label><input type="number" step="0.01" name="monthly_price"></div>
                                    <div class="input-group" style="flex:1"><label>Annual Price ($)</label><input type="number" step="0.01" name="annual_price"></div>
                                </div>
                                <div class="input-group"><label><input type="checkbox" name="is_popular"> Mark as Most Popular</label></div>
                                <div class="input-group"><label>Button Text</label><input type="text" name="button_text"></div>
                                <div class="input-group"><label>Button Link</label><input type="text" name="button_link"></div>
                                
                                <div class="section-title" style="margin-top:20px;">Features List</div>
                                <div id="features-container">
                                    <div class="feature-row">
                                        <input type="text" name="features[0][name]" placeholder="Feature Name (e.g. 30 Articles)" style="flex:1; padding:8px;">
                                        <label><input type="checkbox" name="features[0][is_included]" checked> Included</label>
                                    </div>
                                </div>
                                <button type="button" onclick="addFeature()" style="padding:5px 10px; background:#28a745; color:white; border:none; cursor:pointer; font-size:12px;">+ Add Feature</button>
                            </div>
                            
                            <!-- Right: SEO Data -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">SEO Settings</div>
                                <div class="input-group"><label>SEO Title</label><input type="text" name="seo_title"></div>
                                <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title"></div>
                                <div class="input-group"><label>Author</label><input type="text" name="author"></div>
                                <div class="input-group"><label>Publisher</label><input type="text" name="publisher"></div>
                                <div class="input-group"><label>Copyright</label><input type="text" name="copyright"></div>
                                <div class="input-group"><label>Site Name</label><input type="text" name="site_name"></div>
                                <div class="input-group"><label>Keywords</label><textarea name="keywords" rows="2"></textarea></div>
                                <div class="input-group"><label>SEO Description</label><textarea name="seo_description" rows="2"></textarea></div>
                                <div class="input-group"><label>Meta Description</label><textarea name="meta_description" rows="2"></textarea></div>
                                <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image"></div>
                            </div>
                        </div>
                        <button type="submit" style="padding:10px 20px;background:#17a2b8;color:white;border:none;cursor:pointer; margin-top:20px; width:100%; font-size:16px;">Save Pricing Plan</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
    <script>
        let fCount = 1;
        function addFeature() {
            let html = `<div class="feature-row"><input type="text" name="features[${fCount}][name]" placeholder="Feature Name" style="flex:1; padding:8px;"><label><input type="checkbox" name="features[${fCount}][is_included]" checked> Included</label><button type="button" onclick="this.parentElement.remove()" style="background:#dc3545; color:white; border:none; padding:8px;">X</button></div>`;
            document.getElementById('features-container').insertAdjacentHTML('beforeend', html);
            fCount++;
        }
    </script>
</body>
</html>
