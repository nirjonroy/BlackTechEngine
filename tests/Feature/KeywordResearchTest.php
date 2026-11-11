<?php

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Enums\AiProviderHealthStatus;
use App\Enums\EntitlementKey;
use App\Enums\SearchIntent;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\BrandKeyword;
use App\Models\BrandTopicCluster;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use App\Services\Ai\Gemini\GeminiHttpTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function setupAiKeywordModel(): AiModel
{
    $provider = AiProvider::factory()->create([
        'key' => 'gemini',
        'adapter_type' => 'gemini',
        'is_enabled' => true,
        'health_status' => AiProviderHealthStatus::Healthy,
    ]);
    $model = AiModel::factory()->for($provider, 'provider')->create([
        'provider_model_id' => 'gemini-2.5-flash',
        'modality' => AiModelModality::Text,
        'capabilities' => [AiModelCapability::TextGeneration->value, AiModelCapability::StructuredOutput->value],
        'input_token_limit' => 32000,
        'output_token_limit' => 8192,
        'is_enabled' => true,
        'availability_status' => AiModelAvailabilityStatus::Available,
    ]);
    AiWorkloadRoute::factory()->create([
        'workload_key' => 'seo-geo-content-generation',
        'primary_ai_model_id' => $model->id,
        'is_enabled' => true,
    ]);

    return $model;
}

function enableKeywordEntitlements(Workspace $workspace): WorkspaceSubscription
{
    $plan = PricingPlan::factory()->create();

    return WorkspaceSubscription::factory()->for($workspace)->for($plan, 'plan')->create([
        'entitlement_snapshot' => [
            'plan' => ['id' => $plan->id, 'name' => $plan->name, 'version' => 1],
            'entitlements' => [
                EntitlementKey::ContentStudio->value => ['enabled' => true, 'limit' => null, 'unlimited' => true, 'unit' => null],
                EntitlementKey::AiGenerationAttempts->value => ['enabled' => true, 'limit' => 10, 'unlimited' => false, 'unit' => 'attempt'],
                EntitlementKey::AiTokens->value => ['enabled' => true, 'limit' => 50000, 'unlimited' => false, 'unit' => 'token'],
            ],
        ],
    ]);
}

test('customer can view brand keywords explorer with empty state', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $response = $this->actingAs($user)->get(route('workspaces.brands.keywords.index', [$workspace, $brand]));

    $response->assertOk();
    $response->assertSee('Brand Keyword Explorer');
    $response->assertSee('No keywords found');
});

test('customer can manually add a keyword and create a topic cluster', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    // 1. Create a Topic Cluster
    $clusterResponse = $this->actingAs($user)->post(
        route('workspaces.brands.keyword-clusters.store', [$workspace, $brand]),
        [
            'name' => 'Programmatic SEO Guides',
            'pillar_keyword' => 'programmatic seo',
            'description' => 'Comprehensive guides for scalable SEO pages.',
        ]
    );

    $clusterResponse->assertRedirect(route('workspaces.brands.keywords.index', [$workspace, $brand]));
    $clusterResponse->assertSessionHas('status', 'Topic cluster created successfully.');

    $cluster = BrandTopicCluster::where('brand_id', $brand->id)->first();
    expect($cluster)->not->toBeNull()
        ->and($cluster->name)->toBe('Programmatic SEO Guides')
        ->and($cluster->pillar_keyword)->toBe('programmatic seo');

    // 2. Add a Keyword into that cluster
    $keywordResponse = $this->actingAs($user)->post(
        route('workspaces.brands.keywords.store', [$workspace, $brand]),
        [
            'keyword' => 'Programmatic SEO Examples',
            'brand_topic_cluster_id' => $cluster->id,
            'search_intent' => SearchIntent::Informational->value,
            'notes' => 'Include B2B and directory site case studies.',
        ]
    );

    $keywordResponse->assertRedirect(route('workspaces.brands.keywords.index', [$workspace, $brand]));
    $keywordResponse->assertSessionHas('status', 'Keyword added to brand strategy.');

    $keyword = BrandKeyword::where('brand_id', $brand->id)->first();
    expect($keyword)->not->toBeNull()
        ->and($keyword->keyword)->toBe('Programmatic SEO Examples')
        ->and($keyword->normalized_keyword)->toBe('programmatic seo examples')
        ->and($keyword->search_intent)->toBe(SearchIntent::Informational)
        ->and($keyword->brand_topic_cluster_id)->toBe($cluster->id)
        ->and($keyword->search_volume)->toBeNull()
        ->and($keyword->difficulty_score)->toBeNull();
});

test('duplicate keywords for the same brand are rejected', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'keyword' => 'AI Copywriting Tools',
        'normalized_keyword' => 'ai copywriting tools',
    ]);

    $response = $this->actingAs($user)->post(
        route('workspaces.brands.keywords.store', [$workspace, $brand]),
        [
            'keyword' => '  ai copywriting tools  ',
            'search_intent' => SearchIntent::Commercial->value,
        ]
    );

    $response->assertSessionHasErrors(['keyword']);
    expect(BrandKeyword::where('brand_id', $brand->id)->count())->toBe(1);
});

test('customer can toggle star on a keyword', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $keyword = BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'keyword' => 'Local SEO Checklist',
        'is_starred' => false,
    ]);

    $response = $this->actingAs($user)->patch(
        route('workspaces.brands.keywords.star', [$workspace, $brand, $keyword])
    );

    $response->assertRedirect();
    expect($keyword->fresh()->is_starred)->toBeTrue();

    // Toggle back
    $this->actingAs($user)->patch(
        route('workspaces.brands.keywords.star', [$workspace, $brand, $keyword])
    );
    expect($keyword->fresh()->is_starred)->toBeFalse();
});

test('customer can filter keywords by search term, cluster, and intent', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $clusterA = BrandTopicCluster::factory()->create(['brand_id' => $brand->id, 'workspace_id' => $workspace->id, 'name' => 'Cluster Alpha']);
    $clusterB = BrandTopicCluster::factory()->create(['brand_id' => $brand->id, 'workspace_id' => $workspace->id, 'name' => 'Cluster Beta']);

    BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'brand_topic_cluster_id' => $clusterA->id,
        'keyword' => 'Best Email Marketing Software',
        'search_intent' => SearchIntent::Commercial,
    ]);

    BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'brand_topic_cluster_id' => $clusterB->id,
        'keyword' => 'How to write subject lines',
        'search_intent' => SearchIntent::Informational,
    ]);

    // Filter by cluster A
    $responseA = $this->actingAs($user)->get(route('workspaces.brands.keywords.index', [
        $workspace, $brand, 'cluster_id' => $clusterA->id,
    ]));
    $responseA->assertSee('Best Email Marketing Software');
    $responseA->assertDontSee('How to write subject lines');

    // Filter by Informational intent
    $responseIntent = $this->actingAs($user)->get(route('workspaces.brands.keywords.index', [
        $workspace, $brand, 'intent' => SearchIntent::Informational->value,
    ]));
    $responseIntent->assertSee('How to write subject lines');
    $responseIntent->assertDontSee('Best Email Marketing Software');

    // Filter by text search
    $responseSearch = $this->actingAs($user)->get(route('workspaces.brands.keywords.index', [
        $workspace, $brand, 'search' => 'subject',
    ]));
    $responseSearch->assertSee('How to write subject lines');
    $responseSearch->assertDontSee('Best Email Marketing Software');
});

test('customer can delete a keyword and a cluster', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $cluster = BrandTopicCluster::factory()->create(['brand_id' => $brand->id, 'workspace_id' => $workspace->id]);
    $keyword = BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'brand_topic_cluster_id' => $cluster->id,
    ]);

    // Delete keyword
    $resKw = $this->actingAs($user)->delete(route('workspaces.brands.keywords.destroy', [$workspace, $brand, $keyword]));
    $resKw->assertRedirect();
    expect(BrandKeyword::find($keyword->id))->toBeNull();

    // Delete cluster
    $resCl = $this->actingAs($user)->delete(route('workspaces.brands.keyword-clusters.destroy', [$workspace, $brand, $cluster]));
    $resCl->assertRedirect();
    expect(BrandTopicCluster::find($cluster->id))->toBeNull();
});

test('content studio draft creation pre-fills primary keyword and topic from query parameters', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $response = $this->actingAs($user)->get(route('content-studio.drafts.create', [
        $workspace,
        $brand,
        'primary_keyword' => 'saas onboarding metrics',
        'topic' => 'SaaS Product Growth',
    ]));

    $response->assertOk();
    $response->assertSee('value="saas onboarding metrics"', false);
    $response->assertSee('value="SaaS Product Growth"', false);
});

test('cross-tenant keyword manipulation is strictly forbidden', function () {
    [$userA, $workspaceA, $brandA] = contentStudioTenant();
    [$userB, $workspaceB, $brandB] = contentStudioTenant();

    $keywordB = BrandKeyword::factory()->create([
        'brand_id' => $brandB->id,
        'workspace_id' => $workspaceB->id,
        'keyword' => 'Confidential Strategy Keyword',
    ]);

    // User A attempts to view B's keywords
    $resView = $this->actingAs($userA)->get(route('workspaces.brands.keywords.index', [$workspaceB, $brandB]));
    $resView->assertNotFound();

    // User A attempts to delete B's keyword
    $resDelete = $this->actingAs($userA)->delete(route('workspaces.brands.keywords.destroy', [$workspaceB, $brandB, $keywordB]));
    $resDelete->assertNotFound();
    expect(BrandKeyword::find($keywordB->id))->not->toBeNull();
});

test('ai keyword suggestions request is blocked when safety gate is off', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    config()->set('ai.content_generation_enabled', false);
    Http::preventStrayRequests();

    $response = $this->actingAs($user)->postJson(
        route('workspaces.brands.keywords.ai-suggestions', [$workspace, $brand]),
        ['seed_keyword' => 'enterprise observability']
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('generation');
});

test('ai keyword suggestions returns structured suggestions with duplicate flags and can be imported', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    config()->set('ai.content_generation_enabled', true);
    config()->set('services.gemini.api_key', 'synthetic-test-key');
    enableKeywordEntitlements($workspace);
    setupAiKeywordModel();

    // Pre-exist an existing keyword
    BrandKeyword::factory()->create([
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'keyword' => 'Kubernetes Monitoring Tools',
        'normalized_keyword' => 'kubernetes monitoring tools',
    ]);

    // Mock Gemini API structured response conforming to GeminiProviderAdapter schema
    $clusterPayload = [
        'clusters' => [
            [
                'cluster_name' => 'Kubernetes Monitoring',
                'pillar_keyword' => 'kubernetes monitoring',
                'keywords' => [
                    [
                        'keyword' => 'Kubernetes Monitoring Tools', // duplicate!
                        'search_intent' => 'commercial',
                    ],
                    [
                        'keyword' => 'How to monitor kubernetes cluster',
                        'search_intent' => 'informational',
                    ],
                ],
            ],
            [
                'cluster_name' => 'Cloud Cost Optimization',
                'pillar_keyword' => 'cloud cost optimization',
                'keywords' => [
                    [
                        'keyword' => 'Cloud cost management software',
                        'search_intent' => 'commercial',
                    ],
                ],
            ],
        ],
    ];

    $mockPayload = [
        'seo_title' => 'Kubernetes Monitoring Strategy',
        'meta_description' => 'Comprehensive keyword clusters for Kubernetes monitoring and cost management.',
        'suggested_slug' => 'kubernetes-monitoring-strategy',
        'outline' => ['Overview', 'Cluster Breakdown', 'Execution'],
        'content' => json_encode($clusterPayload, JSON_THROW_ON_ERROR),
        'faq_entries' => [
            ['question' => 'What is kubernetes monitoring?', 'answer' => 'Tracking cluster node and pod metrics.'],
        ],
        'internal_link_opportunities' => ['Kubernetes Strategy'],
        'external_research_topics' => ['CNCF telemetry documentation'],
        'verification_notes' => ['Verify external SEO metric provider configuration.'],
    ];

    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => function (Request $request) use ($mockPayload) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), ':countTokens')) {
                return Http::response(['totalTokens' => 150]);
            }

            return Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($mockPayload, JSON_THROW_ON_ERROR)],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['promptTokenCount' => 150, 'candidatesTokenCount' => 200],
            ]);
        },
    ]);

    // Call suggest API
    $response = $this->actingAs($user)->postJson(
        route('workspaces.brands.keywords.ai-suggestions', [$workspace, $brand]),
        [
            'seed_keyword' => 'cloud monitoring',
            'count' => 10,
        ]
    );

    $response->assertOk();
    $data = $response->json();
    expect($data['suggestions'])->toHaveCount(3);

    // Verify duplicate detection
    $dupItem = collect($data['suggestions'])->firstWhere('keyword', 'Kubernetes Monitoring Tools');
    expect($dupItem['is_duplicate'])->toBeTrue();

    $newItem = collect($data['suggestions'])->firstWhere('keyword', 'How to monitor kubernetes cluster');
    expect($newItem['is_duplicate'])->toBeFalse();

    // Now test bulk import of the new suggestions
    $importResponse = $this->actingAs($user)->post(
        route('workspaces.brands.keywords.import-suggestions', [$workspace, $brand]),
        [
            'clusters' => [
                [
                    'cluster_name' => 'Kubernetes Monitoring',
                    'pillar_keyword' => 'kubernetes monitoring',
                    'keywords' => [
                        [
                            'keyword' => 'How to monitor kubernetes cluster',
                            'search_intent' => 'informational',
                        ],
                    ],
                ],
                [
                    'cluster_name' => 'Cloud Cost Optimization',
                    'pillar_keyword' => 'cloud cost optimization',
                    'keywords' => [
                        [
                            'keyword' => 'Cloud cost management software',
                            'search_intent' => 'commercial',
                        ],
                    ],
                ],
            ],
        ]
    );

    $importResponse->assertRedirect(route('workspaces.brands.keywords.index', [$workspace, $brand]));
    $importResponse->assertSessionHas('status', 'Imported 2 keywords into brand strategy.');

    expect(BrandTopicCluster::where('brand_id', $brand->id)->count())->toBe(2);
    expect(BrandKeyword::where('brand_id', $brand->id)->count())->toBe(3); // 1 existing + 2 imported
});
