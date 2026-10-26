<?php

use App\Enums\AffiliateApplicationStatus;
use App\Enums\AffiliateStatus;
use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateAdminAudit;
use App\Models\AffiliateApplication;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('affiliate foundation tables contain only non-financial fields', function () {
    expect(Schema::hasColumns('affiliate_applications', [
        'id',
        'user_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'review_reason',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('affiliates', [
            'id',
            'user_id',
            'application_id',
            'referral_code',
            'status',
            'approved_at',
            'suspended_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('affiliate_admin_audits', [
            'id',
            'acting_admin_id',
            'subject_type',
            'subject_id',
            'action',
            'request_id',
            'changes',
            'created_at',
        ]))->toBeTrue();

    $foundationColumns = collect([
        ...Schema::getColumnListing('affiliate_applications'),
        ...Schema::getColumnListing('affiliates'),
        ...Schema::getColumnListing('affiliate_admin_audits'),
    ]);

    expect($foundationColumns)->not->toContain(
        'commission_rate',
        'commission_balance',
        'payout_balance',
        'stripe_customer_id',
        'subscription_id',
    );
});

test('applications remain pending and do not create affiliate accounts automatically', function () {
    $user = User::factory()->create();

    $application = $user->affiliateApplications()->create([
        'submitted_at' => now(),
    ]);

    expect($application->status)->toBe(AffiliateApplicationStatus::Pending)
        ->and($application->reviewed_at)->toBeNull()
        ->and($application->reviewed_by_admin_id)->toBeNull()
        ->and($application->review_reason)->toBeNull()
        ->and($application->user->is($user))->toBeTrue()
        ->and($application->affiliate)->toBeNull()
        ->and($user->fresh()->affiliate)->toBeNull();

    $this->assertDatabaseCount('affiliates', 0);
    $this->assertDatabaseCount('affiliate_admin_audits', 0);
});

test('applications require a registered user while review attributes remain nullable', function () {
    expect(fn () => AffiliateApplication::create())
        ->toThrow(QueryException::class);

    $application = AffiliateApplication::create([
        'user_id' => User::factory()->create()->id,
    ]);

    expect($application->submitted_at)->toBeNull()
        ->and($application->reviewed_at)->toBeNull()
        ->and($application->reviewedByAdmin)->toBeNull();
});

test('models reject statuses outside their provisional lifecycle values', function () {
    expect(fn () => AffiliateApplication::create([
        'user_id' => User::factory()->create()->id,
        'status' => 'paid',
    ]))->toThrow(ValueError::class)
        ->and(fn () => Affiliate::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'commissioned',
        ]))->toThrow(ValueError::class);

    $this->assertDatabaseCount('affiliate_applications', 0);
    $this->assertDatabaseCount('affiliates', 0);
});

test('one user can have only one affiliate account', function () {
    $user = User::factory()->create();
    $affiliate = Affiliate::create(['user_id' => $user->id]);

    expect($affiliate->status)->toBe(AffiliateStatus::Pending)
        ->and($affiliate->approved_at)->toBeNull()
        ->and($affiliate->suspended_at)->toBeNull()
        ->and($affiliate->user->is($user))->toBeTrue()
        ->and($user->fresh()->affiliate->is($affiliate))->toBeTrue();

    expect(fn () => Affiliate::create(['user_id' => $user->id]))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('affiliates', 1);
});

test('referral codes are optional normalized and unique when populated', function () {
    $firstAffiliate = Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'referral_code' => '  partner-one  ',
    ]);
    $secondAffiliate = Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'referral_code' => null,
    ]);
    $thirdAffiliate = Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'referral_code' => '   ',
    ]);

    expect($firstAffiliate->referral_code)->toBe('PARTNER-ONE')
        ->and($secondAffiliate->referral_code)->toBeNull()
        ->and($thirdAffiliate->referral_code)->toBeNull();

    expect(fn () => Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'referral_code' => 'partner-one',
    ]))->toThrow(QueryException::class);

    $this->assertDatabaseCount('affiliates', 3);
});

test('an application can be associated with at most one affiliate account', function () {
    $application = AffiliateApplication::create([
        'user_id' => User::factory()->create()->id,
    ]);
    $affiliate = Affiliate::create([
        'user_id' => $application->user_id,
        'application_id' => $application->id,
    ]);

    expect($affiliate->application->is($application))->toBeTrue()
        ->and($application->fresh()->affiliate->is($affiliate))->toBeTrue();

    expect(fn () => Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'application_id' => $application->id,
    ]))->toThrow(QueryException::class);
});

test('an affiliate cannot reference another users application', function () {
    $application = AffiliateApplication::create([
        'user_id' => User::factory()->create()->id,
    ]);

    expect(fn () => Affiliate::create([
        'user_id' => User::factory()->create()->id,
        'application_id' => $application->id,
    ]))->toThrow(QueryException::class);

    $this->assertDatabaseCount('affiliates', 0);
});

test('application history prevents destructive user deletion', function () {
    $user = User::factory()->create();
    $application = AffiliateApplication::create(['user_id' => $user->id]);

    expect(fn () => $user->delete())->toThrow(QueryException::class);

    $this->assertModelExists($user);
    $this->assertModelExists($application);
});

test('affiliate audit changes are cast and survive subject and admin deletion', function () {
    $admin = Admin::create([
        'name' => 'Affiliate Auditor',
        'email' => 'affiliate-auditor@example.com',
        'password' => Hash::make('Password123'),
    ]);
    $application = AffiliateApplication::create([
        'user_id' => User::factory()->create()->id,
    ]);
    $audit = AffiliateAdminAudit::create([
        'acting_admin_id' => $admin->id,
        'subject_type' => AffiliateApplication::class,
        'subject_id' => $application->id,
        'action' => 'application_review_noted',
        'request_id' => (string) Str::uuid(),
        'changes' => ['status' => ['from' => 'pending', 'to' => 'under_review']],
    ]);

    expect($audit->changes)->toBe([
        'status' => ['from' => 'pending', 'to' => 'under_review'],
    ])->and($audit->actingAdmin->is($admin))->toBeTrue()
        ->and($audit->subject->is($application))->toBeTrue()
        ->and($audit->updated_at)->toBeNull();

    $application->delete();
    $admin->delete();

    $this->assertModelExists($audit);
    expect($audit->fresh()->actingAdmin)->toBeNull()
        ->and($audit->fresh()->subject)->toBeNull();
});

test('permission seeding adds only the dedicated admin permission to existing editor access', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['view dashboard', 'create admin', 'edit admin'] as $permissionName) {
        Permission::create(['name' => $permissionName, 'guard_name' => 'admin']);
    }

    $editor = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editor->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::query()
        ->where('name', 'manage affiliates')
        ->where('guard_name', 'admin')
        ->count())->toBe(1)
        ->and(Permission::query()
            ->where('name', 'manage affiliates')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse()
        ->and(Permission::query()
            ->where('name', 'manage affiliate page settings')
            ->where('guard_name', 'admin')
            ->exists())->toBeTrue()
        ->and(Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->firstOrFail()
            ->hasPermissionTo('manage affiliates'))->toBeTrue()
        ->and($editor->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
            'create admin',
            'edit admin',
            'view dashboard',
        ]);
});

test('existing user identity behavior remains compatible with affiliate relationships', function () {
    $user = User::factory()->create([
        'name' => 'Existing User',
        'email' => 'existing-user@example.com',
    ]);

    $user->update(['name' => 'Updated Existing User']);

    expect($user->fresh()->name)->toBe('Updated Existing User')
        ->and($user->affiliateApplications)->toHaveCount(0)
        ->and($user->affiliate)->toBeNull()
        ->and($user->toArray())->not->toHaveKey('password');
});
