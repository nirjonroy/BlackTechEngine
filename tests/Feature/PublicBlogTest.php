<?php

use App\Models\Admin;
use App\Models\Blog;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function createPublishedBlog(array $attributes = []): Blog
{
    return Blog::create(array_merge([
        'title' => fake()->unique()->sentence(4),
        'slug' => fake()->unique()->slug(),
        'content' => fake()->paragraphs(3, true),
        'published_at' => now()->subDay(),
    ], $attributes));
}

test('the blog listing shows only published posts in publication order', function () {
    createPublishedBlog(['title' => 'Older published article', 'slug' => 'older', 'published_at' => now()->subWeek()]);
    createPublishedBlog(['title' => 'Newest published article', 'slug' => 'newest', 'published_at' => now()->subHour()]);
    Blog::create(['title' => 'Private draft', 'slug' => 'private-draft']);
    Blog::create(['title' => 'Scheduled article', 'slug' => 'scheduled', 'published_at' => now()->addDay()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder(['Newest published article', 'Older published article'])
        ->assertDontSee('Private draft')
        ->assertDontSee('Scheduled article')
        ->assertSee('href="'.route('blog.show', 'newest').'"', false);
});

test('a published article resolves by slug while drafts scheduled and unknown slugs return 404', function () {
    $published = createPublishedBlog(['title' => 'Public research', 'slug' => 'public-research', 'content' => 'Verified article body.']);
    Blog::create(['title' => 'Draft research', 'slug' => 'draft-research']);
    Blog::create(['title' => 'Future research', 'slug' => 'future-research', 'published_at' => now()->addHour()]);

    $this->get(route('blog.show', $published->slug))
        ->assertOk()
        ->assertSee('Public research')
        ->assertSee('Verified article body.');
    $this->get(route('blog.show', 'draft-research'))->assertNotFound();
    $this->get(route('blog.show', 'future-research'))->assertNotFound();
    $this->get(route('blog.show', 'missing-research'))->assertNotFound();
});

test('the listing paginates published posts', function () {
    foreach (range(1, 8) as $number) {
        createPublishedBlog([
            'title' => "Article $number",
            'slug' => "article-$number",
            'published_at' => now()->subMinutes($number),
        ]);
    }

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Article 1')
        ->assertDontSee('Article 8')
        ->assertSee('page=2', false);

    $this->get(route('blog.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Article 8')
        ->assertSee('<link rel="canonical" href="'.route('blog.index', ['page' => 2]).'">', false);
});

test('the empty blog state renders without placeholder articles', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('No articles have been published yet.')
        ->assertDontSee('10,000 Prompts Audited');
});

test('article fields are escaped and unsafe image URLs are not rendered', function () {
    $script = '<script>alert("blog")</script>';
    $blog = createPublishedBlog([
        'title' => $script,
        'slug' => 'safe-output',
        'content' => '<img src=x onerror=alert(1)>'.$script,
        'category' => $script,
        'author' => $script,
        'image' => 'javascript:alert(1)',
        'meta_image' => 'data:text/html,unsafe',
    ]);

    $response = $this->get(route('blog.show', $blog->slug));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertDontSee('<img src=x', false)
        ->assertDontSee('src="javascript:', false)
        ->assertDontSee('data:text/html', false);
});

test('article SEO uses its metadata image canonical URL and article fields once', function () {
    $blog = createPublishedBlog([
        'title' => 'Fallback article title',
        'slug' => 'article-seo',
        'meta_title' => 'Article-specific title',
        'meta_description' => 'Article-specific description.',
        'meta_image' => 'seo/article.png',
        'author' => 'Research Team',
    ]);

    $response = $this->get(route('blog.show', $blog->slug));

    $response->assertOk()
        ->assertSee('<title>Article-specific title</title>', false)
        ->assertSee('<meta name="description" content="Article-specific description.">', false)
        ->assertSee('<link rel="canonical" href="'.route('blog.show', $blog->slug).'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/seo/article.png').'">', false)
        ->assertSee('<meta property="article:published_time"', false);

    expect(substr_count($response->getContent(), '<title>'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('related articles use real published category matches only', function () {
    $blog = createPublishedBlog(['title' => 'Primary article', 'slug' => 'primary', 'category' => 'GEO']);
    createPublishedBlog(['title' => 'Related published article', 'slug' => 'related', 'category' => 'GEO']);
    createPublishedBlog(['title' => 'Different category article', 'slug' => 'different', 'category' => 'SEO']);
    Blog::create(['title' => 'Related draft article', 'slug' => 'related-draft', 'category' => 'GEO']);

    $this->get(route('blog.show', $blog->slug))
        ->assertOk()
        ->assertSee('Related published article')
        ->assertDontSee('Different category article')
        ->assertDontSee('Related draft article');
});

test('authorized admins can save drafts and schedule publication while invalid dates are rejected', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $permission = Permission::create(['name' => 'manage blogs', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Blog Editor',
        'email' => 'blog-editor@example.com',
        'password' => Hash::make('Password123'),
    ]);
    $admin->givePermissionTo($permission);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.store'), ['title' => 'Saved draft', 'slug' => 'saved-draft'])
        ->assertRedirect(route('admin.blogs.index'));
    $this->assertDatabaseHas('blogs', ['slug' => 'saved-draft', 'published_at' => null]);

    $publishAt = now()->addDay()->startOfMinute();
    $this->post(route('admin.blogs.store'), [
        'title' => 'Scheduled by editor',
        'slug' => 'scheduled-by-editor',
        'published_at' => $publishAt->format('Y-m-d H:i:s'),
    ])->assertRedirect(route('admin.blogs.index'));
    expect(Blog::where('slug', 'scheduled-by-editor')->firstOrFail()->published_at?->equalTo($publishAt))->toBeTrue();

    $this->post(route('admin.blogs.store'), [
        'title' => 'Invalid schedule',
        'slug' => 'invalid-schedule',
        'published_at' => 'not-a-date',
    ])->assertSessionHasErrors('published_at');
    $this->assertDatabaseMissing('blogs', ['slug' => 'invalid-schedule']);
});

test('publication controls remain protected by blog management permission', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage blogs', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Unprivileged Admin',
        'email' => 'unprivileged-blog@example.com',
        'password' => Hash::make('Password123'),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.store'), [
            'title' => 'Unauthorized publication',
            'slug' => 'unauthorized-publication',
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertForbidden();
    $this->assertDatabaseMissing('blogs', ['slug' => 'unauthorized-publication']);
});
