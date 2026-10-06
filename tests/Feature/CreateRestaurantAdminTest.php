<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateRestaurantAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_active_administrator_when_none_exists(): void
    {
        $password = 'PrivateTestPassword123';

        $this->artisan('restaurant:admin', ['email' => 'admin@gmail.com', '--if-missing' => true])
            ->expectsQuestion('Administrator password (at least 12 characters)', $password)
            ->expectsOutput('Administrator created: admin@gmail.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@gmail.com', 'role' => 'admin', 'is_active' => true]);
        $this->assertTrue(Hash::check($password, User::where('email', 'admin@gmail.com')->firstOrFail()->password));
    }

    public function test_preserves_the_existing_administrator_and_does_not_prompt_for_a_password(): void
    {
        $administrator = User::factory()->create(['role' => 'admin', 'email' => 'owner@example.test']);
        $passwordHash = $administrator->password;

        $this->artisan('restaurant:admin', ['email' => 'admin@gmail.com', '--if-missing' => true])
            ->expectsOutput('Administrator already exists: owner@example.test (active). No account created.')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($passwordHash, $administrator->fresh()->password);
    }

    public function test_reports_an_inactive_administrator_without_reactivating_it(): void
    {
        $administrator = User::factory()->create(['role' => 'admin', 'email' => 'owner@example.test', 'is_active' => false]);

        $this->artisan('restaurant:admin', ['email' => 'admin@gmail.com', '--if-missing' => true])
            ->expectsOutput('Administrator already exists: owner@example.test (inactive). No account created.')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertFalse($administrator->fresh()->is_active);
    }

    public function test_does_not_promote_an_existing_customer_with_the_requested_email(): void
    {
        $customer = User::factory()->create(['email' => 'admin@gmail.com']);

        $this->artisan('restaurant:admin', ['email' => 'admin@gmail.com', '--if-missing' => true])
            ->expectsOutput('Enter a valid email that is not already registered.')
            ->assertFailed();

        $this->assertSame('customer', $customer->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }
}
