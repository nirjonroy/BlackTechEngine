<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>.input-group{margin-bottom:15px;}.input-group label{display:block;font-weight:bold;margin-bottom:5px;}.input-group input,.input-group textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;} .section-title{border-bottom:2px solid #17a2b8; padding-bottom:5px; margin-bottom:15px; font-size:18px; font-weight:bold; color:#333;}</style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Edit Blog</h3>
                    @include('admin.partials.validation-errors')
                    <form action="{{ route('admin.blogs.update', $blog->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <!-- Left: Basic Blog Data -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">Blog Content</div>
                               <div class="input-group">
                                    <label>Blog Title *</label>
                                    <input type="text" name="title" id="title" value="{{ $blog->title }}" required onkeyup="generateSlug()">
                                </div>

                                <div class="input-group">
                                    <label>Slug</label>
                                    <input type="text" name="slug" id="slug" value="{{ $blog->slug }}" required>
                                </div>

                                <div class="input-group"><label>Category</label><input type="text" name="category" value="{{ $blog->category }}"></div>
                                <div class="input-group"><label>Content</label><textarea name="content" rows="10">{{ $blog->content }}</textarea></div>
                                <div class="input-group"><label>Main Blog Image</label><input type="file" name="image">
                                    @if($blog->image) <img src="{{ asset('storage/'.$blog->image) }}" height="50" style="margin-top:10px;"> @endif
                                </div>
                            </div>
                            
                            <!-- Right: SEO Data -->
                            <div style="background:#fafafa; padding:15px; border-radius:5px; border:1px solid #eee;">
                                <div class="section-title">SEO Settings</div>
                                <div class="input-group"><label>SEO Title</label><input type="text" name="seo_title" value="{{ $blog->seo_title }}"></div>
                                <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title" value="{{ $blog->meta_title }}"></div>
                                <div class="input-group"><label>Author</label><input type="text" name="author" value="{{ $blog->author }}"></div>
                                <div class="input-group"><label>Publisher</label><input type="text" name="publisher" value="{{ $blog->publisher }}"></div>
                                <div class="input-group"><label>Copyright</label><input type="text" name="copyright" value="{{ $blog->copyright }}"></div>
                                <div class="input-group"><label>Site Name</label><input type="text" name="site_name" value="{{ $blog->site_name }}"></div>
                                <div class="input-group"><label>Keywords</label><textarea name="keywords" rows="2">{{ $blog->keywords }}</textarea></div>
                                <div class="input-group"><label>SEO Description</label><textarea name="seo_description" rows="2">{{ $blog->seo_description }}</textarea></div>
                                <div class="input-group"><label>Meta Description</label><textarea name="meta_description" rows="2">{{ $blog->meta_description }}</textarea></div>
                                <div class="input-group"><label>Meta Image</label><input type="file" name="meta_image">
                                    @if($blog->meta_image) <img src="{{ asset('storage/'.$blog->meta_image) }}" height="50" style="margin-top:10px;"> @endif
                                </div>
                            </div>
                        </div>
                        <button type="submit" style="padding:10px 20px;background:#17a2b8;color:white;border:none;cursor:pointer; margin-top:20px;">Update Blog</button>
                    </form>
                </div>
            </div>
            <script>
    function generateSlug() {
        let title = document.getElementById('title').value;
        let slug = title.toLowerCase()
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        document.getElementById('slug').value = slug;
    }
</script>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
