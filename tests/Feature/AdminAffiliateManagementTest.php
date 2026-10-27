<?php

use App\Enums\AffiliateApplicationStatus;
use App\Enums\AffiliateStatus;
use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['manage affiliates', 'manage affiliate page settings'] as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createAffiliateModuleAdmin(array $permissions = ['manage affiliates']): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin;
}

function createAffiliateApplicationFor(?User $user = null, array $attributes = []): AffiliateApplication
{
    $user ??= User::factory()->create();

    return AffiliateApplication::create([
        'user_id' => $user->id,
        ...$attributes,
    ]);
}

function createAffiliateAccountFor(?User $user = null, array $attributes = []): Affiliate
{
    $user ??= User::factory()->create();

    return Affiliate::create([
        'user_id' => $user->id,
        ...$attributes,
    ]);
}

test('guests are redirected to the admin login from every affiliate management route', function () {
    $application = createAffiliateApplicationFor();
    $affiliate = createAffiliateAccountFor($application->user, ['application_id' => $application->id]);

    $this->get(route('admin.affiliates.index'))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.affiliates.show', $affiliate))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.affiliate-applications.index'))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.affiliate-applications.show', $application))->assertRedirectToRoute('admin.login');
});

test('regular web users cannot use their session to access affiliate management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.affiliates.index'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('admins without the operational permission receive 403', function () {
    $admin = createAffiliateModuleAdmin([]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index'))
        ->assertForbidden();
});

test('affiliate page cms permission cannot access operational affiliate management', function () {
    $admin = createAffiliateModuleAdmin(['manage affiliate page settings']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate_page.index'))
        ->assertOk();

    $this->get(route('admin.affiliates.index'))->assertForbidden();
    $this->get(route('admin.affiliate-applications.index'))->assertForbidden();
});

test('super admins can access all affiliate management pages without direct permission', function () {
    $admin = createAffiliateModuleAdmin([]);
    $admin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));
    $application = createAffiliateApplicationFor();
    $affiliate = createAffiliateAccountFor($application->user, ['application_id' => $application->id]);

    $this->actingAs($admin, 'admin');

    $this->get(route('admin.affiliates.index'))->assertOk();
    $this->get(route('admin.affiliates.show', $affiliate))->assertOk();
    $this->get(route('admin.affiliate-applications.index'))->assertOk();
    $this->get(route('admin.affiliate-applications.show', $application))->assertOk();
});

test('affiliate management routes are get only and use admin permission middleware', function () {
    $routeNames = [
        'admin.affiliates.index',
        'admin.affiliates.show',
        'admin.affiliate-applications.index',
        'admin.affiliate-applications.show',
    ];

    foreach ($routeNames as $routeName) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toBe(['GET', 'HEAD'])
            ->and($route->gatherMiddleware())->toContain('auth:admin')
            ->and(implode('|', $route->gatherMiddleware()))->toContain('manage affiliates');
    }
});

test('empty affiliate pages render honest setup states', function () {
    $admin = createAffiliateModuleAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index'))
        ->assertOk()
        ->assertSee('Affiliate operations are in setup')
        ->assertSee('No affiliate accounts found')
        ->assertSee('>0<', false);

    $this->get(route('admin.affiliate-applications.index'))
        ->assertOk()
        ->assertSee('No affiliate applications found');
});

test('overview counts come from persisted affiliate records', function () {
    $admin = createAffiliateModuleAdmin();
    $pendingApplication = createAffiliateApplicationFor();
    $approvedApplication = createAffiliateApplicationFor(attributes: [
        'status' => AffiliateApplicationStatus::Approved,
    ]);
    createAffiliateAccountFor($pendingApplication->user, [
        'application_id' => $pendingApplication->id,
    ]);
    createAffiliateAccountFor($approvedApplication->user, [
        'application_id' => $approvedApplication->id,
        'status' => AffiliateStatus::Active,
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.affiliates.index'));

    $response
        ->assertOk()
        ->assertViewHas('totalApplications', 2)
        ->assertViewHas('pendingApplications', 1)
        ->assertViewHas('totalAffiliates', 2)
        ->assertViewHas('activeAffiliates', 1);
});

test('application listings show only matching registered users and status values', function () {
    $admin = createAffiliateModuleAdmin();
    $matchingUser = User::factory()->create(['name' => 'Needle Partner', 'email' => 'needle@example.test']);
    $otherUser = User::factory()->create(['name' => 'Other Partner', 'email' => 'other@example.test']);
    createAffiliateApplicationFor($matchingUser, ['status' => AffiliateApplicationStatus::Pending]);
    createAffiliateApplicationFor($otherUser, ['status' => AffiliateApplicationStatus::Rejected]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate-applications.index', [
            'search' => 'Needle',
            'status' => 'pending',
        ]))
        ->assertOk()
        ->assertSee('Needle Partner')
        ->assertSee('needle@example.test')
        ->assertDontSee('Other Partner');
});

test('affiliate listings search owners and referral codes with stable sorting', function () {
    $admin = createAffiliateModuleAdmin();
    $older = createAffiliateAccountFor(User::factory()->create(['name' => 'Older Owner']), [
        'referral_code' => 'OLDER-CODE',
    ]);
    $newer = createAffiliateAccountFor(User::factory()->create(['name' => 'Newer Owner']), [
        'referral_code' => 'SEARCH-CODE',
        'status' => AffiliateStatus::Active,
    ]);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $newer->forceFill(['created_at' => now()])->save();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index', ['sort' => 'oldest']))
        ->assertOk()
        ->assertSeeInOrder(['Older Owner', 'Newer Owner']);

    $this->get(route('admin.affiliates.index', [
        'search' => 'SEARCH-CODE',
        'status' => 'active',
    ]))
        ->assertOk()
        ->assertSee('Newer Owner')
        ->assertDontSee('Older Owner');
});

test('affiliate pagination is bounded and preserves filters', function () {
    $admin = createAffiliateModuleAdmin();

    foreach (range(1, 11) as $index) {
        createAffiliateAccountFor(User::factory()->create([
            'name' => sprintf('Paged Owner %02d', $index),
        ]));
    }

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index', [
            'search' => 'Paged Owner',
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertViewHas('affiliates', fn ($affiliates): bool => $affiliates->count() === 10 && $affiliates->total() === 11)
        ->assertSee('search=Paged%20Owner', false)
        ->assertSee('per_page=10', false);
});

test('invalid filters and arbitrary sort values are rejected', function () {
    $admin = createAffiliateModuleAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index', [
            'status' => 'paid',
            'sort' => 'users.password',
            'per_page' => 10000,
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors(['status', 'sort', 'per_page']);
});

test('detail pages render supported relationships without sensitive account fields', function () {
    $admin = createAffiliateModuleAdmin();
    $reviewer = createAffiliateModuleAdmin([]);
    $password = Hash::make('sensitive-password');
    $user = User::factory()->create([
        'name' => 'Detail Owner',
        'email' => 'detail@example.test',
        'password' => $password,
        'remember_token' => 'sensitive-token',
    ]);
    $application = createAffiliateApplicationFor($user, [
        'status' => AffiliateApplicationStatus::UnderReview,
        'reviewed_by_admin_id' => $reviewer->id,
        'review_reason' => 'Identity review is pending.',
    ]);
    $affiliate = createAffiliateAccountFor($user, [
        'application_id' => $application->id,
        'referral_code' => 'DETAIL-CODE',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate-applications.show', $application))
        ->assertOk()
        ->assertSee('Detail Owner')
        ->assertSee($reviewer->name)
        ->assertSee('Identity review is pending.')
        ->assertSee('Associated account')
        ->assertDontSee($password)
        ->assertDontSee('sensitive-token');

    $this->get(route('admin.affiliates.show', $affiliate))
        ->assertOk()
        ->assertSee('DETAIL-CODE')
        ->assertSee('View linked application')
        ->assertDontSee($password);
});

test('missing affiliate records return standard 404 responses', function () {
    $admin = createAffiliateModuleAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.show', 999999))
        ->assertNotFound();

    $this->get(route('admin.affiliate-applications.show', 999999))->assertNotFound();
});

test('database text is escaped in affiliate management views', function () {
    $admin = createAffiliateModuleAdmin();
    $user = User::factory()->create([
        'name' => '<script>alert("owner")</script>',
        'email' => 'escape@example.test',
    ]);
    $application = createAffiliateApplicationFor($user, [
        'review_reason' => '<script>alert("reason")</script>',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate-applications.show', $application))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(&quot;owner&quot;)&lt;/script&gt;', false)
        ->assertSee('&lt;script&gt;alert(&quot;reason&quot;)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("owner")</script>', false)
        ->assertDontSee('<script>alert("reason")</script>', false);
});

test('future raw status values render safely without becoming active', function () {
    $admin = createAffiliateModuleAdmin();
    $user = User::factory()->create();
    DB::table('affiliate_applications')->insert([
        'user_id' => $user->id,
        'status' => 'future_state',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate-applications.index'))
        ->assertOk()
        ->assertSee('Future State');

    $this->assertDatabaseMissing('affiliate_applications', ['status' => 'active']);
});

test('navigation shows operational management only to permitted admins', function () {
    $authorizedAdmin = createAffiliateModuleAdmin([
        'manage affiliates',
        'manage affiliate page settings',
    ]);
    $cmsOnlyAdmin = createAffiliateModuleAdmin(['manage affiliate page settings']);

    $this->actingAs($authorizedAdmin, 'admin')
        ->get(route('admin.affiliates.index'))
        ->assertOk()
        ->assertSee('Affiliate Management')
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('admin.affiliates.index'), false)
        ->assertSee('Affiliate Page CMS')
        ->assertDontSee('Affiliate Approvals');

    $this->actingAs($cmsOnlyAdmin, 'admin')
        ->get(route('admin.affiliate_page.index'))
        ->assertOk()
        ->assertDontSee(route('admin.affiliates.index'), false)
        ->assertSee('Affiliate Page Settings');
});

test('affiliate management exposes no fake financial statistics or write routes', function () {
    $admin = createAffiliateModuleAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliates.index'))
        ->assertOk()
        ->assertDontSee('Total Affiliate Sales')
        ->assertDontSee('Pending Payouts')
        ->assertDontSee('Commission Rate')
        ->assertDontSee('Fraud Score');

    $operationalRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.affiliates.')
            || str_starts_with((string) $route->getName(), 'admin.affiliate-applications.'));

    expect($operationalRoutes)->toHaveCount(4);

    foreach ($operationalRoutes as $route) {
        expect($route->methods())->toBe(['GET', 'HEAD']);
    }
});

test('the existing admin dashboard remains available independently', function () {
    Permission::create(['name' => 'view dashboard', 'guard_name' => 'admin']);
    $admin = createAffiliateModuleAdmin(['view dashboard']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Mission Control');
});
