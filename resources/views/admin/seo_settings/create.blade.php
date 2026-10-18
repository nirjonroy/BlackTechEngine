<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>.input-group{margin-bottom:15px;}.input-group label{display:block;font-weight:bold;margin-bottom:5px;}.input-group input,.input-group textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}</style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Create New SEO Setting</h3>
                    @include('admin.partials.validation-errors')
                    <form action="{{ route('admin.seo.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div>
                                <div class="input-group"><label>Page Name</label><input type="text" name="page_name" required></div>
                                <div class="input-group"><label>SEO Title</label><input type="text" name="seo_title"></div>
                                <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title"></div>
                                <div class="input-group"><label>Site Name</label><input type="text" name="site_name"></div>
                                <div class="input-group"><label>Author</label><input type="text" name="author"></div>
                                <div class="input-group"><label>Publisher</label><input type="text" name="publisher"></div>
                                <div class="input-group"><label>Copyright</label><input type="text" name="copyright"></div>
                            </div>
                            <div>
                                <div class="input-group"><label>SEO Description</label><textarea name="seo_description" rows="3"></textarea></div>
                                <div class="input-group"><label>Meta Description</label><textarea name="meta_description" rows="3"></textarea></div>
                                <div class="input-group"><label>Keywords</label><textarea name="keywords" rows="2"></textarea></div>
                                <div class="input-group"><label>Value</label><textarea name="value" rows="2"></textarea></div>
                                <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image"></div>
                            </div>
                        </div>
                        <button type="submit" style="padding:10px 20px;background:#28a745;color:white;border:none;cursor:pointer;">Save</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
