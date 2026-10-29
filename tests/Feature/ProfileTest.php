<?php

use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response
        ->assertOk()
        ->assertSee('name="lock_version"', false)
        ->assertSee('value="0"', false);
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lock_version' => 0,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
    $this->assertSame(1, $user->lock_version);
    $this->assertAuthenticatedAs($user);
});

test('an unchanged profile preserves its version and verification status', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'lock_version' => 0,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $freshUser = $user->fresh();
    expect($freshUser->lock_version)->toBe(0)
        ->and($freshUser->email_verified_at)->not->toBeNull();
});

test('an email-only update resets verification and increments the version', function () {
    $user = User::factory()->create(['name' => 'Same Name']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Same Name',
            'email' => 'new-email@example.test',
            'lock_version' => 0,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $freshUser = $user->fresh();
    expect($freshUser->email)->toBe('new-email@example.test')
        ->and($freshUser->email_verified_at)->toBeNull()
        ->and($freshUser->lock_version)->toBe(1);
});

test('profile updates require a valid non-negative lock version', function (array $version) {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), $version + [
            'name' => 'Changed Name',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('lock_version');

    expect($user->fresh()->name)->toBe('Original Name')
        ->and($user->fresh()->lock_version)->toBe(0);
})->with([
    'missing version' => [[]],
    'non-integer version' => [['lock_version' => 'current']],
    'negative version' => [['lock_version' => -1]],
]);

test('a stale profile update preserves every newer profile field', function () {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'name' => 'Newer Name',
        'email' => 'newer@example.test',
        'email_verified_at' => $verifiedAt,
        'lock_version' => 1,
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Stale Name',
            'email' => 'stale@example.test',
            'lock_version' => 0,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors([
            'lock_version' => 'Your profile changed since you opened this form. Refresh the page and review the latest information before trying again.',
        ]);

    $freshUser = $user->fresh();
    expect($freshUser->name)->toBe('Newer Name')
        ->and($freshUser->email)->toBe('newer@example.test')
        ->and($freshUser->email_verified_at->equalTo($verifiedAt))->toBeTrue()
        ->and($freshUser->lock_version)->toBe(1);
});

test('email uniqueness validation remains enforced without changing profile state', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.test']);
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Changed Name',
            'email' => $existingUser->email,
            'lock_version' => 0,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('email');

    expect($user->fresh()->name)->toBe('Original Name')
        ->and($user->fresh()->email)->toBe($user->email)
        ->and($user->fresh()->lock_version)->toBe(0);
});

test('profile updates trim names and ignore protected-field injection', function () {
    $password = Hash::make('original-password');
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email_verified_at' => $verifiedAt,
        'password' => $password,
        'remember_token' => 'original-token',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => '  Trimmed Name  ',
            'email' => $user->email,
            'lock_version' => 0,
            'email_verified_at' => null,
            'password' => 'attacker-password',
            'remember_token' => 'attacker-token',
            'role' => 'Super Admin',
            'permissions' => ['edit users'],
            'account_status' => 'suspended',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $freshUser = $user->fresh();
    expect($freshUser->name)->toBe('Trimmed Name')
        ->and($freshUser->email_verified_at->equalTo($verifiedAt))->toBeTrue()
        ->and($freshUser->getRawOriginal('password'))->toBe($password)
        ->and($freshUser->remember_token)->toBe('original-token')
        ->and($freshUser->lock_version)->toBe(1);
});

test('guests cannot view or update a profile', function () {
    $user = User::factory()->create();

    $this->get(route('profile.edit'))->assertRedirectToRoute('login');
    $this->patch(route('profile.update'), [
        'name' => 'Changed Name',
        'email' => $user->email,
        'lock_version' => 0,
    ])->assertRedirectToRoute('login');

    expect($user->fresh()->name)->toBe($user->name)
        ->and($user->fresh()->lock_version)->toBe(0);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('a workspace owner cannot delete their account or any workspace data', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();
    $workspace->members()->attach($owner, ['role' => 'owner']);
    $workspace->members()->attach($member, ['role' => 'member']);
    $brand = Brand::factory()->for($workspace)->create();
    $owner->forceFill(['active_workspace_id' => $workspace->id])->save();

    $message = 'Your account owns one or more workspaces. Transfer or otherwise resolve those workspaces before deleting your account.';

    $response = $this->followingRedirects()
        ->actingAs($owner)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password']);

    $response
        ->assertOk()
        ->assertViewIs('profile.edit')
        ->assertSee($message);

    $this->assertAuthenticatedAs($owner);
    $this->assertDatabaseHas('users', ['id' => $owner->id]);
    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'owner_id' => $owner->id]);
    $this->assertDatabaseHas('brands', ['id' => $brand->id, 'workspace_id' => $workspace->id]);
    $this->assertDatabaseHas('user_workspace', [
        'user_id' => $owner->id,
        'workspace_id' => $workspace->id,
        'role' => 'owner',
    ]);
    $this->assertDatabaseHas('user_workspace', [
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
        'role' => 'member',
    ]);
});

test('database constraints reject direct deletion of a workspace owner', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();

    expect(fn () => DB::table('users')->where('id', $owner->id)->delete())
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('users', ['id' => $owner->id]);
    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'owner_id' => $owner->id]);
});

test('workspace membership does not prevent a non-owner from deleting their account', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();
    $workspace->members()->attach($member, ['role' => 'member']);
    $member->forceFill(['active_workspace_id' => $workspace->id])->save();

    $this->actingAs($member)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertDatabaseHas('users', ['id' => $owner->id]);
    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'owner_id' => $owner->id]);
    $this->assertDatabaseMissing('user_workspace', [
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertAuthenticatedAs($user);
    $this->assertNotNull($user->fresh());
});
