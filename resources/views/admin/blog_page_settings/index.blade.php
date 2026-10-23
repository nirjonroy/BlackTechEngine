<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .settings-panel { padding: 18px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; }
        .settings-panel-wide { grid-column: 1 / -1; }
        .section-title { margin: 0 0 16px; padding-bottom: 8px; border-bottom: 2px solid #17a2b8; color: #333; font-size: 18px; font-weight: 700; }
        .input-group { margin-bottom: 15px; }
        .input-group:last-child { margin-bottom: 0; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: 700; }
        .input-group input, .input-group textarea, .input-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: #fff; }
        .input-group textarea { min-height: 86px; resize: vertical; }
        .input-group small { display: block; margin-top: 5px; color: #6b7280; }
        .field-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        .save-button { width: 100%; margin-top: 20px; padding: 14px 22px; border: 0; border-radius: 5px; background: #28a745; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
        .success-message { margin-bottom: 15px; padding: 12px; border: 1px solid #86efac; border-radius: 5px; background: #f0fdf4; color: #166534; }
        @media (max-width: 900px) { .settings-grid, .field-row { grid-template-columns: 1fr; } .settings-panel-wide { grid-column: auto; } }
    </style>
</head>
<body>
<div class="wrapper">
    @include('admin.partials.sidebar')
    <div class="main-content">
        @include('admin.partials.header')
        <div class="content">
            <div class="card">
                <h3>Blog Page Settings</h3>
                <p>Manage public Blog listing and article-detail presentation copy. Article content and publication status remain in the existing Blogs module.</p>

                @if(session('success'))
                    <div class="success-message">{{ session('success') }}</div>
                @endif

                @include('admin.partials.validation-errors')

                <form action="{{ route('admin.blog_page.update') }}" method="POST">
                    @csrf
                    <div class="settings-grid">
                        <section class="settings-panel">
                            <h4 class="section-title">Listing Hero</h4>
                            <div class="input-group"><label for="hero_badge">Badge</label><input id="hero_badge" name="hero_badge" value="{{ old('hero_badge', $setting->hero_badge) }}" placeholder="Editorial &amp; Research Lab"></div>
                            <div class="field-row">
                                <div class="input-group"><label for="hero_title">Title</label><input id="hero_title" name="hero_title" value="{{ old('hero_title', $setting->hero_title) }}" placeholder="Mastering Generative Engine Optimization &amp;"></div>
                                <div class="input-group"><label for="hero_highlighted_title">Highlighted title</label><input id="hero_highlighted_title" name="hero_highlighted_title" value="{{ old('hero_highlighted_title', $setting->hero_highlighted_title) }}" placeholder="Autonomous SEO"></div>
                            </div>
                            <div class="input-group"><label for="hero_description">Description</label><textarea id="hero_description" name="hero_description" placeholder="Research, practical frameworks, and data-backed insights...">{{ old('hero_description', $setting->hero_description) }}</textarea></div>
                        </section>

                        <section class="settings-panel">
                            <h4 class="section-title">Search &amp; Categories</h4>
                            <div class="input-group"><label for="search_label">Search field label</label><input id="search_label" name="search_label" value="{{ old('search_label', $setting->search_label) }}" placeholder="Search articles"></div>
                            <div class="input-group"><label for="search_placeholder">Search placeholder</label><input id="search_placeholder" name="search_placeholder" value="{{ old('search_placeholder', $setting->search_placeholder) }}" placeholder="Search guides, GEO tactics, AI prompts..."></div>
                            <div class="field-row">
                                <div class="input-group"><label for="filter_heading">Filter accessibility label</label><input id="filter_heading" name="filter_heading" value="{{ old('filter_heading', $setting->filter_heading) }}" placeholder="Filter articles by category"></div>
                                <div class="input-group"><label for="all_posts_label">All-posts label</label><input id="all_posts_label" name="all_posts_label" value="{{ old('all_posts_label', $setting->all_posts_label) }}" placeholder="All Posts"></div>
                            </div>
                            <small>Category buttons are generated from real published Blog records.</small>
                        </section>

                        <section class="settings-panel">
                            <h4 class="section-title">Listing Labels</h4>
                            <div class="field-row">
                                <div class="input-group"><label for="featured_label">Featured article label</label><input id="featured_label" name="featured_label" value="{{ old('featured_label', $setting->featured_label) }}" placeholder="Latest article"></div>
                                <div class="input-group"><label for="minutes_read_label">Reading-time suffix</label><input id="minutes_read_label" name="minutes_read_label" value="{{ old('minutes_read_label', $setting->minutes_read_label) }}" placeholder="min read"></div>
                            </div>
                            <div class="field-row">
                                <div class="input-group"><label for="read_full_article_label">Featured CTA label</label><input id="read_full_article_label" name="read_full_article_label" value="{{ old('read_full_article_label', $setting->read_full_article_label) }}" placeholder="Read full article"></div>
                                <div class="input-group"><label for="read_article_label">Article-card CTA label</label><input id="read_article_label" name="read_article_label" value="{{ old('read_article_label', $setting->read_article_label) }}" placeholder="Read article"></div>
                            </div>
                        </section>

                        <section class="settings-panel">
                            <h4 class="section-title">Listing Empty State</h4>
                            <div class="input-group"><label for="empty_state_heading">Heading</label><input id="empty_state_heading" name="empty_state_heading" value="{{ old('empty_state_heading', $setting->empty_state_heading) }}" placeholder="Research is being prepared"></div>
                            <div class="input-group"><label for="empty_state_description">Description</label><textarea id="empty_state_description" name="empty_state_description" placeholder="No articles have been published yet...">{{ old('empty_state_description', $setting->empty_state_description) }}</textarea></div>
                            <div class="input-group"><label for="no_results_heading">Filtered-results heading</label><input id="no_results_heading" name="no_results_heading" value="{{ old('no_results_heading', $setting->no_results_heading) }}" placeholder="No matching articles"></div>
                            <div class="input-group"><label for="no_results_description">Filtered-results description</label><textarea id="no_results_description" name="no_results_description" placeholder="Try another search term or category.">{{ old('no_results_description', $setting->no_results_description) }}</textarea></div>
                        </section>

                        <section class="settings-panel">
                            <h4 class="section-title">Article Detail Labels</h4>
                            <div class="field-row">
                                <div class="input-group"><label for="back_to_blog_label">Blog breadcrumb label</label><input id="back_to_blog_label" name="back_to_blog_label" value="{{ old('back_to_blog_label', $setting->back_to_blog_label) }}" placeholder="Blog"></div>
                                <div class="input-group"><label for="published_label">Publication label</label><input id="published_label" name="published_label" value="{{ old('published_label', $setting->published_label) }}" placeholder="Published"></div>
                            </div>
                            <div class="input-group"><label for="article_empty_state">Missing article-content message</label><textarea id="article_empty_state" name="article_empty_state" placeholder="This article does not have any content yet.">{{ old('article_empty_state', $setting->article_empty_state) }}</textarea></div>
                            <div class="input-group"><label for="related_articles_heading">Related-articles heading</label><input id="related_articles_heading" name="related_articles_heading" value="{{ old('related_articles_heading', $setting->related_articles_heading) }}" placeholder="Related Articles"></div>
                        </section>

                        <section class="settings-panel">
                            <h4 class="section-title">Article Sidebar CTA</h4>
                            <div class="input-group"><label for="sidebar_heading">Heading</label><input id="sidebar_heading" name="sidebar_heading" value="{{ old('sidebar_heading', $setting->sidebar_heading) }}" placeholder="Build search authority on autopilot"></div>
                            <div class="input-group"><label for="sidebar_description">Description</label><textarea id="sidebar_description" name="sidebar_description" placeholder="Connect your site and turn expert ideas into a consistent publishing workflow.">{{ old('sidebar_description', $setting->sidebar_description) }}</textarea></div>
                            <div class="field-row">
                                <div class="input-group"><label for="sidebar_cta_label">CTA label</label><input id="sidebar_cta_label" name="sidebar_cta_label" value="{{ old('sidebar_cta_label', $setting->sidebar_cta_label) }}" placeholder="Start free"></div>
                                <div class="input-group"><label for="sidebar_cta_destination">Approved destination</label><select id="sidebar_cta_destination" name="sidebar_cta_destination"><option value="">Create account (default)</option>@foreach($destinationOptions as $value => $label)<option value="{{ $value }}" @selected(old('sidebar_cta_destination', $setting->sidebar_cta_destination) === $value)>{{ $label }}</option>@endforeach</select></div>
                            </div>
                            <div class="input-group"><label for="sidebar_note">Supporting note</label><input id="sidebar_note" name="sidebar_note" value="{{ old('sidebar_note', $setting->sidebar_note) }}" placeholder="Explore the available account workflow."></div>
                        </section>
                    </div>

                    <button class="save-button" type="submit">Save Blog Page Settings</button>
                </form>
            </div>
        </div>
        @include('admin.partials.footer')
    </div>
</div>
</body>
</html>
