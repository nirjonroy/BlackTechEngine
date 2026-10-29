<?php

use App\Models\Admin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

test('a regular customer cannot use the web guard to enter admin routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertRedirectToRoute('admin.login');

    $this->assertAuthenticatedAs($user, 'web');
    $this->assertGuest('admin');
});

test('an admin session does not grant access to customer workspace routes', function () {
    $admin = Admin::create([
        'name' => 'Isolated Admin',
        'email' => 'isolated-admin@example.test',
        'password' => Hash::make('password'),
    ]);
    Auth::guard('admin')->login($admin);

    $this->get(route('workspaces.index'))->assertRedirectToRoute('login');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
});

test('authorized workspace membership permits access without transferring ownership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create(['name' => 'Shared Workspace']);
    $workspace->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)->get(route('workspaces.show', $workspace))
        ->assertRedirectToRoute('workspaces.brands.index', $workspace);
    expect($workspace->fresh()->owner_id)->toBe($owner->id);
});
