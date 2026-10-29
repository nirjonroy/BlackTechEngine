<?php

use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;

test('registration starts guided customer onboarding', function () {
    $response = $this->post(route('register'), [
        'name' => 'New Customer',
        'email' => 'customer@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirectToRoute('onboarding.start');
    $this->assertAuthenticated();
});

test('onboarding continues at the first incomplete step for existing customers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('onboarding.start'))->assertRedirectToRoute('onboarding.workspace.create');

    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $this->actingAs($user)->get(route('onboarding.start'))->assertRedirectToRoute('onboarding.brand.create', $workspace);

    $brand = Brand::factory()->for($workspace)->create();
    $this->actingAs($user)->get(route('onboarding.start'))->assertRedirectToRoute('onboarding.confirm', [$workspace, $brand]);
});

test('a customer completes workspace and brand onboarding without external actions', function () {
    $user = User::factory()->create();

    $workspaceResponse = $this->actingAs($user)->post(route('onboarding.workspace.store'), validWorkspacePayload());
    $workspace = Workspace::query()->sole();
    $workspaceResponse->assertRedirectToRoute('onboarding.brand.create', $workspace);

    $brandResponse = $this->actingAs($user)->post(route('onboarding.brand.store', $workspace), validBrandPayload());
    $brand = Brand::query()->sole();
    $brandResponse->assertRedirectToRoute('onboarding.confirm', [$workspace, $brand]);

    $this->actingAs($user)->get(route('onboarding.confirm', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('does not call Gemini');
    $this->actingAs($user)->post(route('onboarding.complete', [$workspace, $brand]))
        ->assertRedirectToRoute('dashboard');

    expect($user->fresh()->active_workspace_id)->toBe($workspace->id);
});
