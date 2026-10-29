<?php

use App\Models\User;
use App\Models\Workspace;

test('guests are redirected to customer login from workspace routes', function () {
    $this->get(route('workspaces.index'))->assertRedirectToRoute('login');
    $this->post(route('workspaces.store'), validWorkspacePayload())->assertRedirectToRoute('login');
});

test('a customer creates an owned active workspace with future membership readiness', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workspaces.store'), validWorkspacePayload());

    $response->assertRedirectToRoute('workspaces.index')->assertSessionHasNoErrors();
    $workspace = Workspace::query()->sole();
    expect($workspace->owner_id)->toBe($user->id)
        ->and($workspace->name)->toBe('Acme Growth')
        ->and($workspace->settings)->toBe(['timezone' => 'Asia/Dhaka'])
        ->and($user->fresh()->active_workspace_id)->toBe($workspace->id);
    $this->assertDatabaseHas('user_workspace', [
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'role' => 'owner',
    ]);
});

test('workspace index includes owned and authorized memberships but excludes other customers', function () {
    $user = User::factory()->create();
    $owned = Workspace::factory()->for($user, 'owner')->create(['name' => 'Owned Workspace']);
    $memberWorkspace = Workspace::factory()->create(['name' => 'Member Workspace']);
    $memberWorkspace->members()->attach($user, ['role' => 'member']);
    $other = Workspace::factory()->create(['name' => 'Private Workspace']);

    $this->actingAs($user)->get(route('workspaces.index'))
        ->assertOk()
        ->assertSee($owned->name)
        ->assertSee($memberWorkspace->name)
        ->assertDontSee($other->name);
});

test('an authorized customer updates workspace settings without protected-field injection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->patch(route('workspaces.update', $workspace), validWorkspacePayload([
        'name' => '  Updated Workspace  ',
        'owner_id' => $otherUser->id,
        'settings' => ['unsafe' => true],
    ]))->assertRedirectToRoute('workspaces.index')->assertSessionHasNoErrors();

    $workspace->refresh();
    expect($workspace->name)->toBe('Updated Workspace')
        ->and($workspace->owner_id)->toBe($user->id)
        ->and($workspace->settings)->toBe(['timezone' => 'Asia/Dhaka']);
});

test('another customer cannot view update or activate a workspace', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create(['name' => 'Owner Workspace']);

    $this->actingAs($attacker)->get(route('workspaces.show', $workspace))->assertNotFound();
    $this->actingAs($attacker)->patch(route('workspaces.update', $workspace), validWorkspacePayload())->assertForbidden();
    $this->actingAs($attacker)->put(route('workspaces.active.update', $workspace))->assertForbidden();

    expect($workspace->fresh()->name)->toBe('Owner Workspace')
        ->and($attacker->fresh()->active_workspace_id)->toBeNull();
});

test('workspace validation rejects blank names and unsupported timezones', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('workspaces.create'))->post(route('workspaces.store'), [
        'name' => '   ',
        'timezone' => 'Mars/Olympus',
    ])->assertRedirectToRoute('workspaces.create')->assertSessionHasErrors(['name', 'timezone']);

    $this->assertDatabaseCount('workspaces', 0);
});
