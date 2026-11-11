<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Enums\AiProviderHealthStatus;
use App\Enums\WordPressConnectionStatus;
use App\Models\AiContentDraft;
use App\Models\AiContentDraftImage;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\WordPressSiteConnection;
use App\Services\Ai\Images\AiImageGenerationManager;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('ai.content_studio_enabled', true);
    config()->set('ai.image_generation_enabled', true);
    config()->set('services.gemini.api_key', 'test-fake-gemini-key');
    config()->set('wordpress.allow_local_urls', true);
    Storage::fake('public');
});

function createValidPngBinary(int $width = 200, int $height = 200): string
{
    $png = chr(0x89).'PNG'.chr(0x0D).chr(0x0A).chr(0x1A).chr(0x0A)
        .pack('N', 13).'IHDR'.pack('NN', $width, $height).chr(8).chr(2).chr(0).chr(0).chr(0);
    $crc = pack('N', crc32('IHDR'.pack('NN', $width, $height).chr(8).chr(2).chr(0).chr(0).chr(0)));
    $png .= $crc;
    $raw = str_repeat(chr(0).str_repeat(chr(0), $width * 3), $height);
    $idat = gzcompress($raw);
    $png .= pack('N', strlen($idat)).'IDAT'.$idat.pack('N', crc32('IDAT'.$idat));
    $png .= pack('N', 0).'IEND'.pack('N', crc32('IEND'));

    return $png;
}

function setupImageGenerationEnvironment(bool $withImageCapability = true): array
{
    $provider = AiProvider::factory()->create([
        'key' => 'gemini',
        'adapter_type' => 'gemini',
        'is_enabled' => true,
        'health_status' => AiProviderHealthStatus::Healthy,
    ]);

    $model = AiModel::factory()->for($provider, 'provider')->create([
        'provider_model_id' => $withImageCapability ? 'imagen-3.0-generate-002' : 'gemini-2.5-flash',
        'modality' => $withImageCapability ? AiModelModality::Image : AiModelModality::Text,
        'capabilities' => $withImageCapability
            ? [AiModelCapability::ImageGeneration->value]
            : [AiModelCapability::TextGeneration->value],
        'is_enabled' => true,
        'availability_status' => AiModelAvailabilityStatus::Available,
    ]);

    AiWorkloadRoute::factory()->create([
        'workload_key' => AiImageGenerationManager::WORKLOAD_KEY,
        'primary_ai_model_id' => $model->id,
        'is_enabled' => true,
    ]);

    return [$provider, $model];
}

test('image generation is blocked when feature flag is disabled', function () {
    config()->set('ai.image_generation_enabled', false);
    [$user, $workspace, $brand] = contentStudioTenant();

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
        'topic' => 'SaaS Lead Generation',
    ]);

    $response = $this->actingAs($user)->post(
        route('content-studio.drafts.featured-image.generate', [$workspace, $brand, $draft]),
        ['instructions' => 'Minimalist render']
    );

    $response->assertSessionHasErrors(['feature']);
    expect(AiContentDraftImage::count())->toBe(0);
});

test('unsupported text model without image capability fails closed', function () {
    setupImageGenerationEnvironment(withImageCapability: false);
    [$user, $workspace, $brand] = contentStudioTenant();

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
        'topic' => 'Conversion Optimization',
    ]);

    $response = $this->actingAs($user)->post(
        route('content-studio.drafts.featured-image.generate', [$workspace, $brand, $draft])
    );

    $response->assertSessionHasErrors(['generation']);
    expect(AiContentDraftImage::count())->toBe(0);
});

test('customer can generate featured image with eligible image model', function () {
    setupImageGenerationEnvironment(withImageCapability: true);
    [$user, $workspace, $brand] = contentStudioTenant();

    $validPng = createValidPngBinary(300, 200);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'predictions' => [
                [
                    'bytesBase64Encoded' => base64_encode($validPng),
                    'mimeType' => 'image/png',
                ],
            ],
        ], 200),
    ]);

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
        'topic' => 'Advanced Technical SEO',
        'seo_title' => 'The Complete Technical SEO Guide',
    ]);

    $response = $this->actingAs($user)->post(
        route('content-studio.drafts.featured-image.generate', [$workspace, $brand, $draft]),
        ['instructions' => 'Modern isometric 3D']
    );

    $response->assertRedirect(route('content-studio.drafts.show', [$workspace, $brand, $draft]));
    $response->assertSessionHas('status', 'AI featured image generated successfully.');

    $image = AiContentDraftImage::first();
    expect($image)->not->toBeNull()
        ->and($image->is_active)->toBeTrue()
        ->and($image->width)->toBe(300)
        ->and($image->height)->toBe(200)
        ->and($image->mime_type)->toBe('image/png')
        ->and($image->file_size_bytes)->toBe(strlen($validPng))
        ->and($image->prompt)->toContain('The Complete Technical SEO Guide')
        ->and($image->prompt)->toContain('Modern isometric 3D')
        ->and($image->alt_text)->not->toBeEmpty();

    Storage::disk('public')->assertExists($image->image_path);
});

test('regenerating featured image deactivates previous active image', function () {
    setupImageGenerationEnvironment(withImageCapability: true);
    [$user, $workspace, $brand] = contentStudioTenant();

    $validPng = createValidPngBinary(250, 250);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'predictions' => [
                [
                    'bytesBase64Encoded' => base64_encode($validPng),
                    'mimeType' => 'image/png',
                ],
            ],
        ], 200),
    ]);

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
        'topic' => 'Keyword Cannibalization Guide',
    ]);

    // First generation
    $this->actingAs($user)->post(
        route('content-studio.drafts.featured-image.generate', [$workspace, $brand, $draft])
    );

    $firstImage = AiContentDraftImage::first();
    expect($firstImage->is_active)->toBeTrue();

    // Second generation (regenerate)
    $this->actingAs($user)->post(
        route('content-studio.drafts.featured-image.generate', [$workspace, $brand, $draft]),
        ['instructions' => 'Vibrant contrast']
    );

    expect(AiContentDraftImage::count())->toBe(2);
    expect($firstImage->fresh()->is_active)->toBeFalse();

    $secondImage = AiContentDraftImage::latest('id')->first();
    expect($secondImage->is_active)->toBeTrue();
    expect($draft->fresh()->featuredImage->id)->toBe($secondImage->id);
});

test('customer can update alt text of active featured image', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
    ]);

    $image = AiContentDraftImage::factory()->for($draft, 'draft')->for($brand)->for($workspace)->create([
        'is_active' => true,
        'alt_text' => 'Initial alt text',
    ]);

    $response = $this->actingAs($user)->patch(
        route('content-studio.drafts.featured-image.alt-text', [$workspace, $brand, $draft]),
        ['alt_text' => 'Updated optimized SEO alt text']
    );

    $response->assertRedirect(route('content-studio.drafts.show', [$workspace, $brand, $draft]));
    expect($image->fresh()->alt_text)->toBe('Updated optimized SEO alt text');
});

test('customer can remove active featured image', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
    ]);

    $image = AiContentDraftImage::factory()->for($draft, 'draft')->for($brand)->for($workspace)->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->delete(
        route('content-studio.drafts.featured-image.destroy', [$workspace, $brand, $draft])
    );

    $response->assertRedirect(route('content-studio.drafts.show', [$workspace, $brand, $draft]));
    expect($image->fresh()->is_active)->toBeFalse();
    expect($draft->fresh()->featuredImage)->toBeNull();
});

test('wordpress publishing uploads and attaches featured media when active image is present', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $connection = WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://example.com',
        'username' => 'editor',
        'application_password' => 'secret-app-password',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $draft = AiContentDraft::factory()->for($workspace)->for($brand)->create([
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::Approved,
        'seo_title' => 'Top 10 B2B Marketing Tactics',
        'content' => 'Full article body',
    ]);

    $validPng = createValidPngBinary(200, 200);
    $storagePath = "featured-images/{$brand->id}/b2b-marketing-tactics.png";
    Storage::disk('public')->put($storagePath, $validPng);

    $image = AiContentDraftImage::factory()->for($draft, 'draft')->for($brand)->for($workspace)->create([
        'image_path' => $storagePath,
        'file_name' => 'b2b-marketing-tactics.png',
        'alt_text' => 'B2B Marketing Tactics visual',
        'is_active' => true,
        'wordpress_media_id' => null,
    ]);

    Http::fake([
        'https://example.com/wp-json/wp/v2/media*' => Http::response([
            'id' => 999,
            'source_url' => 'https://example.com/wp-content/uploads/b2b-marketing-tactics.png',
        ], 201),
        'https://example.com/wp-json/wp/v2/posts*' => function (Request $request) {
            $data = $request->data();
            expect($data)->toHaveKey('featured_media')
                ->and($data['featured_media'])->toBe(999);

            return Http::response([
                'id' => 888,
                'link' => 'https://example.com/blog/b2b-marketing-tactics',
                'status' => 'publish',
            ], 201);
        },
    ]);

    $publisher = app(WordPressPublishingService::class);
    $publishedPost = $publisher->publish($draft, $user, 'publish');

    expect($publishedPost->wordpress_post_id)->toBe(888);
    expect($image->fresh()->wordpress_media_id)->toBe(999);
});

test('tenant boundary blocks foreign user from modifying featured images', function () {
    setupImageGenerationEnvironment(withImageCapability: true);
    [$userA, $workspaceA, $brandA] = contentStudioTenant();
    [$userB, $workspaceB, $brandB] = contentStudioTenant();

    $draftA = AiContentDraft::factory()->for($workspaceA)->for($brandA)->create([
        'created_by_user_id' => $userA->id,
    ]);

    // User B tries to generate image on Draft A
    $response = $this->actingAs($userB)->post(
        route('content-studio.drafts.featured-image.generate', [$workspaceA, $brandA, $draftA])
    );
    expect($response->status())->toBeIn([403, 404]);

    // User B tries to update alt text on Draft A
    $response = $this->actingAs($userB)->patch(
        route('content-studio.drafts.featured-image.alt-text', [$workspaceA, $brandA, $draftA]),
        ['alt_text' => 'Malicious edit']
    );
    expect($response->status())->toBeIn([403, 404]);

    // User B tries to delete image on Draft A
    $response = $this->actingAs($userB)->delete(
        route('content-studio.drafts.featured-image.destroy', [$workspaceA, $brandA, $draftA])
    );
    expect($response->status())->toBeIn([403, 404]);
});
