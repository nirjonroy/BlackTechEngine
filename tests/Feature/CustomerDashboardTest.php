<?php

use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;

test('a guest is redirected from the customer dashboard to customer login', function () {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('an existing customer without a workspace sees an honest onboarding empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Create your first workspace')
        ->assertSee('Start onboarding')
        ->assertDontSee('Generated 1,000');
});

test('the dashboard renders only real workspace and brand counts', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => 'Growth Lab']);
    $workspace->members()->attach($user, ['role' => 'owner']);
    $activeBrand = Brand::factory()->for($workspace)->create(['name' => 'Active Brand', 'is_active' => true]);
    Brand::factory()->for($workspace)->create(['name' => 'Paused Brand', 'is_active' => false]);
    $user->active_workspace_id = $workspace->id;
    $user->save();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('activeWorkspace', fn (Workspace $value): bool => $value->is($workspace))
        ->assertViewHas('recentBrands', fn ($brands): bool => $brands->contains($activeBrand))
        ->assertSee('Growth Lab')
        ->assertSee('Active Brand')
        ->assertSee('Not connected yet')
        ->assertSee('No drafts are available');
    expect($response->viewData('activeWorkspace')->brands_count)->toBe(2)
        ->and($response->viewData('activeWorkspace')->active_brands_count)->toBe(1);
});

test('customer supplied dashboard content is escaped', function () {
    $user = User::factory()->create(['name' => '<script>alert("user")</script>']);
    $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => '<img src=x onerror=alert(1)>']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertDontSee($user->name, false)
        ->assertSee($workspace->name)
        ->assertDontSee($workspace->name, false);
});
