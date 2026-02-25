<?php

use App\Enums\QrCodeStatus;
use App\Models\LoanTier;
use App\Models\MerchantLocation;
use App\Models\QrCode;
use App\Models\Stamp;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

// ── User CRUD ────────────────────────────────────────────────────────────

it('lists users as admin', function () {
    Sanctum::actingAs($this->admin);

    User::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/users')
        ->assertSuccessful();
});

it('creates a user as admin', function () {
    Sanctum::actingAs($this->admin);

    $file = UploadedFile::fake()->image('avatar.jpg');
    $this->postJson('/api/v1/admin/users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'gender' => 'male',
        'city' => 'Test City',
        'profile_picture' => $file,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'New User');

    $created = User::where('email', 'newuser@example.com')->first();
    expect($created->getFirstMedia('avatar'))->not->toBeNull();
});

it('validates required fields for user creation', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/users', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    // ensure at least one of email or mobile is required
    $this->postJson('/api/v1/admin/users', [
        'name' => 'Foo',
        'password' => 'password123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'mobile']);
});

it('validates unique email for user creation', function () {
    Sanctum::actingAs($this->admin);

    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/admin/users', [
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'password123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('deletes a user as admin', function () {
    Sanctum::actingAs($this->admin);

    $user = User::factory()->create();

    $this->deleteJson('/api/v1/admin/users/'.$user->id)
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('denies user management to regular users', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/users')
        ->assertForbidden();
});

// ── Stamps (read-only) ───────────────────────────────────────────────────

it('lists stamps as admin', function () {
    Sanctum::actingAs($this->admin);

    Stamp::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/stamps')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

// ── QR Codes ─────────────────────────────────────────────────────────────

it('lists QR codes as admin', function () {
    Sanctum::actingAs($this->admin);

    QrCode::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/qr-codes')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('generates a batch of QR codes', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/qr-codes/generate-batch', [
        'count' => 5,
        'prefix' => 'TST',
    ])->assertCreated()
        ->assertJsonPath('message', '5 QR codes generated successfully.');

    expect(QrCode::count())->toBe(5);
});

it('validates batch count limits', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/qr-codes/generate-batch', [
        'count' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['count']);
});

it('links a QR code to a merchant location', function () {
    Sanctum::actingAs($this->admin);

    $qrCode = QrCode::factory()->create();
    $location = MerchantLocation::factory()->create();

    $this->postJson('/api/v1/admin/qr-codes/'.$qrCode->id.'/link', [
        'unique_code' => $qrCode->unique_code,
        'merchant_location_id' => $location->id,
    ])->assertSuccessful()
        ->assertJsonPath('data.status', QrCodeStatus::Linked->value);
});

// ── Loan Tiers ───────────────────────────────────────────────────────────

it('lists loan tiers as admin', function () {
    Sanctum::actingAs($this->admin);

    LoanTier::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/loan-tiers')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('creates a loan tier', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/loan-tiers', [
        'min_streak_months' => 6,
        'max_loan_amount' => 50000,
        'interest_rate_percentage' => 5,
        'description' => 'Test tier',
    ])->assertCreated()
        ->assertJsonPath('data.min_streak_months', 6);
});

it('validates min_streak_months minimum for loan tiers', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/loan-tiers', [
        'min_streak_months' => 1,
        'max_loan_amount' => 50000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['min_streak_months']);
});

it('denies loan tier management to regular users', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/loan-tiers')
        ->assertForbidden();
});

// ── Roles & Permissions ──────────────────────────────────────────────────

it('lists roles as admin', function () {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/admin/roles')
        ->assertSuccessful();
});

it('creates a role with permissions', function () {
    Sanctum::actingAs($this->admin);

    $permissions = Permission::take(3)->pluck('id')->toArray();

    $this->postJson('/api/v1/admin/roles', [
        'name' => 'Test Role',
        'guard_name' => 'web',
        'permissions' => $permissions,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Test Role');
});

it('validates unique role name', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/roles', [
        'name' => 'Super Admin',
        'guard_name' => 'web',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('lists permissions as admin', function () {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/admin/permissions')
        ->assertSuccessful();
});

it('creates a permission', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/permissions', [
        'name' => 'custom-permission',
        'guard_name' => 'web',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'custom-permission');
});

it('denies role management to regular users', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/roles')
        ->assertForbidden();
});
