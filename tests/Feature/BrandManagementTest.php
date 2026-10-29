<?php

use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;

test('an authorized customer creates a normalized brand profile', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->post(route('workspaces.brands.store', $workspace), validBrandPayload());

    $brand = Brand::query()->sole();
    $response->assertRedirectToRoute('workspaces.brands.show', [$workspace, $brand])->assertSessionHasNoErrors();
    expect($brand->content_objectives)->toBe(['Build qualified traffic', 'Educate buyers'])
        ->and($brand->seo_topic_categories)->toBe(['Technical SEO', 'Content operations'])
        ->and($brand->is_active)->toBeTrue();
});

test('a customer can view and update a brand including inactive status', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $brand = Brand::factory()->for($workspace)->create(['name' => 'Original Brand']);

    $this->actingAs($user)->get(route('workspaces.brands.show', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('Original Brand');

    $this->actingAs($user)->patch(route('workspaces.brands.update', [$workspace, $brand]), validBrandPayload([
        'name' => 'Updated Brand',
        'is_active' => '0',
        'workspace_id' => Workspace::factory()->create()->id,
    ]))->assertRedirectToRoute('workspaces.brands.show', [$workspace, $brand])->assertSessionHasNoErrors();

    expect($brand->fresh()->name)->toBe('Updated Brand')
        ->and($brand->fresh()->is_active)->toBeFalse()
        ->and($brand->fresh()->workspace_id)->toBe($workspace->id);
});

test('brand validation rejects unsafe urls duplicates and missing context', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->from(route('workspaces.brands.create', $workspace))->post(
        route('workspaces.brands.store', $workspace),
        validBrandPayload([
            'website_url' => 'javascript:alert(1)',
            'business_description' => '',
            'content_objectives' => "Traffic\ntraffic",
            'seo_topic_categories' => '',
        ]),
    )->assertRedirectToRoute('workspaces.brands.create', $workspace)
        ->assertSessionHasErrors(['website_url', 'business_description', 'content_objectives.1', 'seo_topic_categories']);

    $this->assertDatabaseCount('brands', 0);
});

test('nested brand bindings and policies prevent cross-workspace disclosure', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $ownerWorkspace = Workspace::factory()->for($owner, 'owner')->create();
    $attackerWorkspace = Workspace::factory()->for($attacker, 'owner')->create();
    $privateBrand = Brand::factory()->for($ownerWorkspace)->create(['name' => 'Private Brand']);

    $this->actingAs($attacker)->get(route('workspaces.brands.show', [$ownerWorkspace, $privateBrand]))->assertNotFound();
    $this->actingAs($attacker)->get(route('workspaces.brands.show', [$attackerWorkspace, $privateBrand]))->assertNotFound();
    $this->actingAs($attacker)->patch(route('workspaces.brands.update', [$ownerWorkspace, $privateBrand]), validBrandPayload())->assertForbidden();

    expect($privateBrand->fresh()->name)->toBe('Private Brand');
});

test('brand management exposes no delete route', function () {
    expect(Route::has('workspaces.brands.destroy'))->toBeFalse();
});
