<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleRunStatus;
use App\Enums\ArticleScheduleTargetStatus;
use App\Models\AiContentDraft;
use App\Models\ArticleSchedule;
use App\Models\ArticleScheduleRun;
use App\Models\BrandKeyword;
use App\Models\BrandTopicCluster;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Autopilot\AutopilotProcessor;

beforeEach(function () {
    config()->set('ai.content_studio_enabled', true);
});

test('customer can render article calendar in month, week, and list views', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    foreach (['month', 'week', 'list'] as $view) {
        $response = $this->actingAs($user)->get(route('content-studio.calendar', [
            'workspace' => $workspace->id,
            'brand' => $brand->id,
            'view' => $view,
        ]));

        $response->assertOk();
        $response->assertSee('Article Calendar');
    }
});

test('customer can create an article schedule linked to a brand and saved keyword', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $cluster = BrandTopicCluster::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'name' => 'SEO Growth Cluster',
    ]);

    $keyword = BrandKeyword::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'brand_topic_cluster_id' => $cluster->id,
        'keyword' => 'saas marketing guide',
    ]);

    $response = $this->actingAs($user)->post(route('workspaces.brands.schedules.store', [$workspace, $brand]), [
        'title' => 'Complete SaaS Marketing Strategy 2026',
        'primary_keyword' => 'saas marketing guide',
        'secondary_keywords' => 'b2b demand, conversion rate',
        'brand_keyword_id' => $keyword->id,
        'brand_topic_cluster_id' => $cluster->id,
        'content_type' => AiContentType::SeoBlogArticle->value,
        'content_length' => AiContentLength::Standard->value,
        'tone' => 'Authoritative and practical',
        'target_audience' => 'SaaS Founders',
        'language' => 'en',
        'region' => 'US',
        'frequency' => ArticleScheduleFrequency::Daily->value,
        'scheduled_time' => '14:30',
        'timezone' => 'UTC',
        'target_status' => ArticleScheduleTargetStatus::Draft->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('article_schedules', [
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'primary_keyword' => 'saas marketing guide',
        'brand_keyword_id' => $keyword->id,
        'frequency' => ArticleScheduleFrequency::Daily->value,
        'is_active' => true,
    ]);

    $schedule = ArticleSchedule::where('primary_keyword', 'saas marketing guide')->first();
    expect($schedule->next_run_at)->not->toBeNull();
});

test('customer can create weekly recurring schedule with specific days', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $response = $this->actingAs($user)->post(route('workspaces.brands.schedules.store', [$workspace, $brand]), [
        'title' => 'Weekly B2B Newsletter Roundup',
        'primary_keyword' => 'b2b newsletter',
        'content_type' => AiContentType::SeoBlogArticle->value,
        'content_length' => AiContentLength::Short->value,
        'frequency' => ArticleScheduleFrequency::Weekly->value,
        'scheduled_time' => '09:00',
        'timezone' => 'America/New_York',
        'days_of_week' => [1, 3, 5], // Mon, Wed, Fri
        'target_status' => ArticleScheduleTargetStatus::NeedsReview->value,
    ]);

    $response->assertRedirect();
    $schedule = ArticleSchedule::where('title', 'Weekly B2B Newsletter Roundup')->first();
    expect($schedule)->not->toBeNull()
        ->and($schedule->days_of_week)->toBe([1, 3, 5])
        ->and($schedule->next_run_at)->not->toBeNull();
});

test('customer can pause and resume an article schedule', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $schedule = ArticleSchedule::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'is_active' => true,
    ]);

    // 1. Pause
    $response = $this->actingAs($user)->patch(route('workspaces.brands.schedules.toggle', [$workspace, $brand, $schedule]));
    $response->assertRedirect();
    expect($schedule->fresh()->is_active)->toBeFalse();

    // 2. Resume
    $response = $this->actingAs($user)->patch(route('workspaces.brands.schedules.toggle', [$workspace, $brand, $schedule]));
    $response->assertRedirect();
    expect($schedule->fresh()->is_active)->toBeTrue();
});

test('customer can delete an article schedule', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $schedule = ArticleSchedule::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('workspaces.brands.schedules.destroy', [$workspace, $brand, $schedule]));
    $response->assertRedirect();
    $this->assertDatabaseMissing('article_schedules', ['id' => $schedule->id]);
});

test('cross-tenant schedule manipulation is strictly forbidden', function () {
    [$userA, $workspaceA, $brandA] = contentStudioTenant();
    [$userB, $workspaceB, $brandB] = contentStudioTenant();

    $scheduleB = ArticleSchedule::factory()->create([
        'workspace_id' => $workspaceB->id,
        'brand_id' => $brandB->id,
        'created_by_user_id' => $userB->id,
    ]);

    // Attacker A attempts to delete Schedule B
    $this->actingAs($userA)
        ->delete(route('workspaces.brands.schedules.destroy', [$workspaceA, $brandA, $scheduleB]))
        ->assertNotFound();

    // Attacker A attempts to toggle Schedule B
    $this->actingAs($userA)
        ->patch(route('workspaces.brands.schedules.toggle', [$workspaceA, $brandA, $scheduleB]))
        ->assertNotFound();
});

test('artisan autopilot command warns and exits cleanly when safety gates are disabled', function () {
    config()->set('ai.autopilot_enabled', false);
    config()->set('ai.content_generation_enabled', false);

    $this->artisan('autopilot:process')
        ->expectsOutputToContain('Autopilot execution is disabled globally')
        ->assertSuccessful();

    expect(ArticleScheduleRun::count())->toBe(0);
});

test('autopilot processor executes eligible schedule and records successful run with draft', function () {
    config()->set('ai.autopilot_enabled', true);
    config()->set('ai.content_generation_enabled', true);

    [$user, $workspace, $brand] = contentStudioTenant();

    $schedule = ArticleSchedule::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'frequency' => ArticleScheduleFrequency::Daily,
        'is_active' => true,
        'next_run_at' => now()->subMinute(),
    ]);

    $mockDraft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'topic' => $schedule->title,
        'primary_keyword' => $schedule->primary_keyword,
        'status' => AiContentDraftStatus::NeedsReview,
    ]);

    $mockGenerationManager = Mockery::mock(AiContentGenerationManager::class);
    $mockGenerationManager->shouldReceive('generate')
        ->once()
        ->andReturn($mockDraft);

    $this->app->instance(AiContentGenerationManager::class, $mockGenerationManager);

    $processor = app(AutopilotProcessor::class);
    $result = $processor->process($schedule);

    expect($result['success'])->toBeTrue()
        ->and($result['draft_id'])->toBe($mockDraft->id);

    $run = ArticleScheduleRun::where('article_schedule_id', $schedule->id)->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe(ArticleScheduleRunStatus::Completed)
        ->and($run->ai_content_draft_id)->toBe($mockDraft->id);

    $schedule->refresh();
    expect($schedule->last_run_at)->not->toBeNull()
        ->and($schedule->failure_count)->toBe(0)
        ->and($schedule->total_runs)->toBe(1)
        ->and($schedule->next_run_at->isAfter(now()))->toBeTrue();
});

test('autopilot processor records failures and pauses schedule when max retries exceeded', function () {
    config()->set('ai.autopilot_enabled', true);
    config()->set('ai.content_generation_enabled', true);
    config()->set('ai.autopilot_max_retries', 3);

    [$user, $workspace, $brand] = contentStudioTenant();

    $schedule = ArticleSchedule::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'is_active' => true,
        'failure_count' => 2,
        'next_run_at' => now()->subMinute(),
    ]);

    $mockGenerationManager = Mockery::mock(AiContentGenerationManager::class);
    $mockGenerationManager->shouldReceive('generate')
        ->once()
        ->andThrow(new RuntimeException('Gemini synthetic quota exceeded'));

    $this->app->instance(AiContentGenerationManager::class, $mockGenerationManager);

    $processor = app(AutopilotProcessor::class);
    $result = $processor->process($schedule);

    expect($result['success'])->toBeFalse();

    $schedule->refresh();
    expect($schedule->failure_count)->toBe(3)
        ->and($schedule->is_active)->toBeFalse() // Paused!
        ->and($schedule->last_error)->toContain('Gemini synthetic quota exceeded');

    $run = ArticleScheduleRun::where('article_schedule_id', $schedule->id)->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe(ArticleScheduleRunStatus::Failed)
        ->and($run->error_message)->toContain('Gemini synthetic quota exceeded');
});

test('artisan autopilot command processes due schedules bounded by limit', function () {
    config()->set('ai.autopilot_enabled', true);
    config()->set('ai.content_generation_enabled', true);

    [$user, $workspace, $brand] = contentStudioTenant();

    $scheduleA = ArticleSchedule::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'is_active' => true,
        'next_run_at' => now()->subMinutes(10),
    ]);

    $mockDraft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
    ]);

    $mockGenerationManager = Mockery::mock(AiContentGenerationManager::class);
    $mockGenerationManager->shouldReceive('generate')->andReturn($mockDraft);
    $this->app->instance(AiContentGenerationManager::class, $mockGenerationManager);

    $this->artisan('autopilot:process', ['--limit' => 5])
        ->expectsOutputToContain('Autopilot run finished')
        ->assertSuccessful();

    expect(ArticleScheduleRun::where('article_schedule_id', $scheduleA->id)->count())->toBe(1);
});
