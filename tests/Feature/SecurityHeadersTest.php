<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function assertBaselineSecurityHeaders(TestResponse $response): void
{
    $expectedHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Permissions-Policy' => 'camera=(), geolocation=(), microphone=()',
    ];

    foreach ($expectedHeaders as $name => $value) {
        $response->assertHeader($name, $value);
        expect($response->headers->all($name))->toBe([$value]);
    }
}

test('public and authentication pages receive baseline security headers', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertOk();
    assertBaselineSecurityHeaders($response);
})->with([
    'homepage' => ['home'],
    'about page' => ['about'],
    'user login' => ['login'],
    'admin login' => ['admin.login'],
]);

test('an authenticated admin response retains authorization and baseline security headers', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'view dashboard', 'guard_name' => 'admin']);
    $admin = Admin::create([
        'name' => 'Security Header Admin',
        'email' => 'security-header-admin@example.com',
        'password' => Hash::make('Password123'),
    ]);
    $admin->givePermissionTo('view dashboard');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertOk();
    $this->assertAuthenticatedAs($admin, 'admin');
    assertBaselineSecurityHeaders($response);
});

test('an unauthenticated admin redirect retains its correct destination and baseline security headers', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirectToRoute('admin.login');
    $this->assertGuest('admin');
    assertBaselineSecurityHeaders($response);
});

test('a forbidden admin response retains its status and baseline security headers', function () {
    $admin = Admin::create([
        'name' => 'Unauthorized Admin',
        'email' => 'unauthorized-admin@example.com',
        'password' => Hash::make('Password123'),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertForbidden();
    assertBaselineSecurityHeaders($response);
});

test('an intentionally configured response header is not overwritten', function () {
    Route::get('/security-header-override-test', fn () => response('ok')->header('X-Frame-Options', 'DENY'));

    $response = $this->get('/security-header-override-test');

    $response
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY');
    expect($response->headers->all('X-Frame-Options'))->toBe(['DENY']);
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});
