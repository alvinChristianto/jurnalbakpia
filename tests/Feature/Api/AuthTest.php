<?php

namespace Tests\Feature\Api;

use App\Models\OlCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_customer_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'phone_number' => '081234567890',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'budi@example.com');

        $this->assertDatabaseHas('ol_customers', ['email' => 'budi@example.com']);

        $customer = OlCustomer::where('email', 'budi@example.com')->first();
        $this->assertTrue(Hash::check('rahasia123', $customer->password));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        OlCustomer::create([
            'name' => 'Existing',
            'email' => 'taken@example.com',
            'password' => Hash::make('whatever1'),
        ]);

        $this->postJson('/api/register', [
            'name' => 'Someone Else',
            'email' => 'taken@example.com',
            'password' => 'password1',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_google_registered_email_with_custom_error_code(): void
    {
        OlCustomer::create([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'password' => Hash::make('anything1'),
            'google_id' => 'gid-123',
        ]);

        $this->postJson('/api/register', [
            'name' => 'Google User',
            'email' => 'google@example.com',
            'password' => 'password1',
        ])->assertStatus(422)
          ->assertJsonPath('error_code', 'google_account_exists');
    }

    public function test_register_requires_password_of_at_least_eight_chars(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => 'short',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('password');
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        OlCustomer::create([
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'correct-password',
        ])->assertOk()
          ->assertJsonStructure(['access_token', 'token_type', 'user'])
          ->assertJsonPath('user.email', 'login@example.com');
    }

    public function test_login_with_wrong_password_returns_validation_error(): void
    {
        OlCustomer::create([
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');
    }

    public function test_profile_route_is_protected(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_profile_route_returns_authenticated_customer(): void
    {
        $customer = OlCustomer::create([
            'name' => 'Me',
            'email' => 'me@example.com',
            'password' => Hash::make('password1'),
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('email', 'me@example.com')
            ->assertJsonPath('id', $customer->id);
    }

    public function test_logout_revokes_current_token(): void
    {
        $customer = OlCustomer::create([
            'name' => 'Bye',
            'email' => 'bye@example.com',
            'password' => Hash::make('password1'),
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->assertSame(1, $customer->tokens()->count());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar');

        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_google_callback_creates_new_customer_when_email_unknown(): void
    {
        $response = $this->postJson('/api/auth/google/callback', [
            'name' => 'Google New',
            'email' => 'newgoogle@example.com',
            'google_id' => 'gid-new-1',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'user']);
        $this->assertDatabaseHas('ol_customers', [
            'email' => 'newgoogle@example.com',
            'google_id' => 'gid-new-1',
        ]);
    }

    public function test_google_callback_links_existing_customer(): void
    {
        OlCustomer::create([
            'name' => 'Old Name',
            'email' => 'existing@example.com',
            'password' => Hash::make('password1'),
        ]);

        $this->postJson('/api/auth/google/callback', [
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
            'google_id' => 'gid-existing',
        ])->assertOk();

        $this->assertDatabaseHas('ol_customers', [
            'email' => 'existing@example.com',
            'google_id' => 'gid-existing',
            'name' => 'Updated Name',
        ]);
    }
}
