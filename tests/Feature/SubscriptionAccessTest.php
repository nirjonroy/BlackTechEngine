<?php

use App\Models\Admin;
use App\Models\BillingInvoice;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\BillingInvoicePolicy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

test('subscription routes are absent while management gate is disabled', function () {
    if (config('subscriptions.management_enabled')) {
        $this->markTestSkipped('Subscription management routes are enabled for this test process.');
    }

    expect(config('subscriptions.management_enabled'))->toBeFalse()
        ->and(Route::has('subscriptions.index'))->toBeFalse()
        ->and(Route::has('admin.subscriptions.index'))->toBeFalse();
});

test('invoice policy enforces customer tenant separation', function () {
    [$owner, $workspace, , $subscription] = subscriptionFixture();
    $other = User::factory()->create();
    $invoice = BillingInvoice::factory()->for($subscription, 'subscription')->for($workspace)->create();
    $policy = app(BillingInvoicePolicy::class);

    expect($policy->view($owner, $invoice)->allowed())->toBeTrue()
        ->and($policy->view($other, $invoice)->denied())->toBeTrue()
        ->and($policy->delete($owner, $invoice))->toBeFalse();
});

test('public pricing remains available with the subscription gates disabled', function () {
    $this->get('/pricing')->assertOk();
});

test('enabled customer subscription routes require authentication and render for a customer', function () {
    if (! config('subscriptions.management_enabled')) {
        $this->markTestSkipped('Subscription management routes are disabled for the default test process.');
    }

    $this->get(route('subscriptions.index'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    Workspace::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();
});

test('enabled admin routes enforce the dedicated permission', function () {
    if (! config('subscriptions.management_enabled')) {
        $this->markTestSkipped('Subscription management routes are disabled for the default test process.');
    }

    $admin = Admin::query()->create([
        'name' => 'Subscription Admin',
        'email' => 'subscription-admin@example.test',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($admin, 'admin')->get(route('admin.subscriptions.index'))->assertForbidden();
    Permission::findOrCreate('manage subscriptions', 'admin');
    $admin->givePermissionTo('manage subscriptions');

    $this->actingAs($admin, 'admin')->get(route('admin.subscriptions.index'))->assertOk();
});
