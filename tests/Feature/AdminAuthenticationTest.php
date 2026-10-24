<?php

use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

function createAuthenticatableAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'name' => 'Security Admin',
        'email' => 'security-admin@example.com',
        'password' => Hash::make('Password123'),
    ], $attributes));
}

test('admin login screen can be rendered', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('action="'.url('/admin/login').'"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="remember"', false);
});

test('an admin can authenticate through the admin guard and regenerates the session', function () {
    $admin = createAuthenticatableAdmin();
    session()->start();
    $previousSessionId = session()->getId();

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'Password123',
    ]);

    $response->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
    expect(session()->getId())->not->toBe($previousSessionId);
});

test('admin login rejects missing and invalid input', function (array $payload, array $invalidFields) {
    $this->post('/admin/login', $payload)
        ->assertSessionHasErrors($invalidFields);

    $this->assertGuest('admin');
})->with([
    'missing credentials' => [[], ['email', 'password']],
    'invalid email' => [['email' => 'not-an-email', 'password' => 'Password123'], ['email']],
    'missing password' => [['email' => 'admin@example.com'], ['password']],
]);

test('admin login rejects an email longer than its database column', function () {
    $overlongEmail = str_repeat('a', 244).'@example.com';

    $this->post('/admin/login', [
        'email' => $overlongEmail,
        'password' => 'Password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('failed admin login attempts are locked after five failures', function () {
    $admin = createAuthenticatableAdmin();
    Event::fake([Lockout::class]);

    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'Password123',
    ])->assertSessionHasErrors('email');

    Event::assertDispatched(Lockout::class);
    $this->assertGuest('admin');
});

test('admin throttle keys normalize email and isolate different account identifiers', function () {
    $uppercaseRequest = AdminLoginRequest::create('/admin/login', 'POST', [
        'email' => 'Security-Admin@EXAMPLE.COM',
    ], server: ['REMOTE_ADDR' => '203.0.113.10']);
    $lowercaseRequest = AdminLoginRequest::create('/admin/login', 'POST', [
        'email' => 'security-admin@example.com',
    ], server: ['REMOTE_ADDR' => '203.0.113.10']);
    $differentAccountRequest = AdminLoginRequest::create('/admin/login', 'POST', [
        'email' => 'other-admin@example.com',
    ], server: ['REMOTE_ADDR' => '203.0.113.10']);

    expect($uppercaseRequest->throttleKey())
        ->toBe($lowercaseRequest->throttleKey())
        ->not->toBe($differentAccountRequest->throttleKey());
});

test('one locked account identifier does not lock another account on the same IP address', function () {
    $limitedAdmin = createAuthenticatableAdmin(['email' => 'limited-admin@example.com']);
    $otherAdmin = createAuthenticatableAdmin(['email' => 'other-admin@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', [
            'email' => $limitedAdmin->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/admin/login', [
        'email' => $otherAdmin->email,
        'password' => 'Password123',
    ])->assertRedirect('/admin/dashboard');

    $this->assertAuthenticatedAs($otherAdmin, 'admin');
});

test('successful admin authentication clears previous failed attempts', function () {
    $admin = createAuthenticatableAdmin(['email' => 'reset-admin@example.com']);
    $throttleKey = Str::transliterate(Str::lower($admin->email).'|127.0.0.1');

    foreach (range(1, 2) as $attempt) {
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    expect(RateLimiter::attempts($throttleKey))->toBe(2);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'Password123',
    ])->assertRedirect('/admin/dashboard');

    expect(RateLimiter::attempts($throttleKey))->toBe(0);
});

test('admin authentication honors the intended redirect', function () {
    $admin = createAuthenticatableAdmin();

    $this->withSession(['url.intended' => '/admin/settings'])
        ->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'Password123',
        ])->assertRedirect('/admin/settings');

    $this->assertAuthenticatedAs($admin, 'admin');
});

test('admin logout invalidates the session and regenerates its token', function () {
    $admin = createAuthenticatableAdmin();
    $this->actingAs($admin, 'admin');
    session()->put('sensitive-session-value', 'present');
    $previousSessionId = session()->getId();
    $previousToken = session()->token();

    $this->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest('admin');
    expect(session()->getId())->not->toBe($previousSessionId)
        ->and(session()->token())->not->toBe($previousToken)
        ->and(session()->has('sensitive-session-value'))->toBeFalse();
});

test('admin login failures do not reveal whether an account exists', function () {
    $admin = createAuthenticatableAdmin();

    $existingAccountResponse = $this->from(route('admin.login'))->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'incorrect-password',
    ]);
    $missingAccountResponse = $this->from(route('admin.login'))->post('/admin/login', [
        'email' => 'missing-admin@example.com',
        'password' => 'incorrect-password',
    ]);

    $existingAccountResponse->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $missingAccountResponse->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest('admin');
});
