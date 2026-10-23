<?php

use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogPageSetting;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage blog page settings', 'guard_name' => 'admin']);
});

function blogPageAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => 'Blog Page Editor',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage blog page settings');
    }

    return $admin;
}

function publishedBlogForPage(array $attributes = []): Blog
{
    return Blog::create(array_merge([
        'title' => fake()->unique()->sentence(4),
        'slug' => fake()->unique()->slug(),
        'content' => fake()->paragraphs(2, true),
        'published_at' => now()->subDay(),
    ], $attributes));
}

test('blog page settings require the dedicated permission and allow the Super Admin bypass', function () {
    $this->actingAs(blogPageAdmin(false), 'admin')
        ->get(route('admin.blog_page.index'))
        ->assertForbidden();

    $superAdmin = blogPageAdmin(false);
    $superAdmin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.blog_page.index'))
        ->assertOk()
        ->assertSee('Blog Page Settings');
});

test('an authorized administrator saves listing and detail presentation settings', function () {
    $admin = blogPageAdmin();
    $payload = [
        'hero_badge' => 'Editorial Database',
        'hero_title' => 'Research for',
        'hero_highlighted_title' => 'Answer Engines',
        'hero_description' => 'Evidence-led publishing guidance.',
        'search_label' => 'Search the research library',
        'search_placeholder' => 'Find a practical guide',
        'filter_heading' => 'Research categories',
        'all_posts_label' => 'Every article',
        'featured_label' => 'Featured research',
        'read_full_article_label' => 'Open the research',
        'read_article_label' => 'Read this guide',
        'minutes_read_label' => 'minute read',
        'empty_state_heading' => 'Research is coming',
        'empty_state_description' => 'Published work will appear here.',
        'no_results_heading' => 'Nothing matched',
        'no_results_description' => 'Choose another category.',
        'back_to_blog_label' => 'Research library',
        'published_label' => 'Released',
        'article_empty_state' => 'Article text is being prepared.',
        'related_articles_heading' => 'Continue reading',
        'sidebar_heading' => 'Explore BlackTechEngine',
        'sidebar_description' => 'Review the platform before creating an account.',
        'sidebar_cta_label' => 'View features',
        'sidebar_cta_destination' => 'features',
        'sidebar_note' => 'No purchase is made from this page.',
    ];

    $response = $this->actingAs($admin, 'admin')
        ->from(route('admin.blog_page.index'))
        ->post(route('admin.blog_page.update'), $payload);

    $response->assertRedirect(route('admin.blog_page.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('blog_page_settings', [
        'hero_badge' => 'Editorial Database',
        'sidebar_cta_destination' => 'features',
    ]);

    $blog = publishedBlogForPage(['title' => 'Configured article', 'slug' => 'configured-article']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Editorial Database')
        ->assertSee('Answer Engines')
        ->assertSee('Find a practical guide')
        ->assertSee('Featured research')
        ->assertSee('Open the research');

    $this->get(route('blog.show', $blog->slug))
        ->assertOk()
        ->assertSee('Research library')
        ->assertSee('Explore BlackTechEngine')
        ->assertSee('No purchase is made from this page.')
        ->assertSee('href="'.route('features').'"', false);
});

test('invalid settings are rejected without changing saved content', function () {
    $setting = BlogPageSetting::create([
        'hero_badge' => 'Preserved badge',
        'sidebar_cta_destination' => 'login',
    ]);

    $response = $this->actingAs(blogPageAdmin(), 'admin')
        ->post(route('admin.blog_page.update'), [
            'hero_badge' => str_repeat('x', 256),
            'sidebar_cta_destination' => 'javascript:alert(1)',
        ]);

    $response->assertSessionHasErrors(['hero_badge', 'sidebar_cta_destination']);
    expect($setting->refresh()->hero_badge)->toBe('Preserved badge')
        ->and($setting->sidebar_cta_destination)->toBe('login');
});

test('category filtering uses published categories and preserves the filter through pagination', function () {
    foreach (range(1, 8) as $number) {
        publishedBlogForPage([
            'title' => "GEO article $number",
            'slug' => "geo-article-$number",
            'category' => 'GEO',
            'published_at' => now()->subMinutes($number),
        ]);
    }
    publishedBlogForPage(['title' => 'SEO article', 'slug' => 'seo-article', 'category' => 'SEO']);
    Blog::create(['title' => 'Draft GEO article', 'slug' => 'draft-geo', 'category' => 'GEO']);
    Blog::create(['title' => 'Scheduled GEO article', 'slug' => 'scheduled-geo', 'category' => 'GEO', 'published_at' => now()->addDay()]);

    $firstPage = $this->get(route('blog.index', ['category' => 'GEO']));

    $firstPage->assertOk()
        ->assertSee('GEO article 1')
        ->assertDontSee('SEO article')
        ->assertDontSee('Draft GEO article')
        ->assertDontSee('Scheduled GEO article')
        ->assertSee('category=GEO', false)
        ->assertSee('page=2', false)
        ->assertSee('<link rel="canonical" href="'.route('blog.index', ['category' => 'GEO']).'">', false);

    $this->get(route('blog.index', ['category' => 'GEO', 'page' => 2]))
        ->assertOk()
        ->assertSee('GEO article 8')
        ->assertDontSee('SEO article');
});

test('search filters only published articles and keeps active category parameters', function () {
    publishedBlogForPage(['title' => 'Answer engine field guide', 'slug' => 'answer-engine-guide', 'category' => 'Research']);
    publishedBlogForPage(['title' => 'Unrelated published article', 'slug' => 'unrelated', 'category' => 'Research']);
    Blog::create([
        'title' => 'Answer engine private draft',
        'slug' => 'answer-engine-private',
        'category' => 'Research',
    ]);

    $this->get(route('blog.index', ['q' => 'field guide', 'category' => 'Research']))
        ->assertOk()
        ->assertSee('Answer engine field guide')
        ->assertDontSee('Unrelated published article')
        ->assertDontSee('Answer engine private draft')
        ->assertSee('value="field guide"', false)
        ->assertSee('name="category" value="Research"', false);
});

test('CMS text is escaped and an unsafe stored CTA destination cannot become a link', function () {
    $script = '<script>alert("blog settings")</script>';
    BlogPageSetting::create([
        'hero_badge' => $script,
        'sidebar_heading' => $script,
        'sidebar_description' => $script,
        'sidebar_cta_label' => $script,
        'sidebar_cta_destination' => 'javascript:alert(1)',
    ]);
    $blog = publishedBlogForPage(['title' => 'Safe article', 'slug' => 'safe-article']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false);

    $this->get(route('blog.show', $blog->slug))
        ->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertDontSee('javascript:alert(1)', false)
        ->assertSee('href="'.route('register').'"', false);
});
