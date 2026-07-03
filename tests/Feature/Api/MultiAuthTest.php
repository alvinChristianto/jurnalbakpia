<?php

namespace Tests\Feature\Api;

use App\Mail\OlCustomerResetPasswordMail;
use App\Models\OlCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_on_passwordless_account_returns_oauth_only(): void
    {
        OlCustomer::create([
            'name' => 'G',
            'email' => 'g@example.com',
            'password' => null,
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/login', [
            'email' => 'g@example.com',
            'password' => 'anything',
        ])->assertStatus(422)->assertJsonPath('error_code', 'oauth_only');
    }

    public function test_google_login_evicts_unverified_squatter_password(): void
    {
        $customer = OlCustomer::create([
            'name' => 'Squatter',
            'email' => 'victim@example.com',
            'password' => Hash::make('squatter-pass'),
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/auth/google/callback', [
            'name' => 'Real Victim',
            'email' => 'victim@example.com',
            'google_id' => 'gid-victim',
        ])->assertOk();

        $customer->refresh();
        $this->assertNull($customer->password);
        $this->assertNotNull($customer->email_verified_at);

        // Old password no longer works — the account is now Google-only.
        $this->postJson('/api/login', [
            'email' => 'victim@example.com',
            'password' => 'squatter-pass',
        ])->assertStatus(422)->assertJsonPath('error_code', 'oauth_only');
    }

    public function test_forgot_password_is_enumeration_safe(): void
    {
        Mail::fake();
        OlCustomer::create([
            'name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('password1'),
        ]);

        $known = $this->postJson('/api/forgot-password', ['email' => 'a@example.com'])->assertOk();
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        $this->assertSame($known->json('message'), $unknown->json('message'));
        // Mailable is ShouldQueue, so under Mail::fake() it lands in the queued bucket.
        Mail::assertQueued(OlCustomerResetPasswordMail::class, 1); // only for the known email
    }

    public function test_reset_password_sets_password_for_oauth_only_account(): void
    {
        $customer = OlCustomer::create([
            'name' => 'G', 'email' => 'g@example.com', 'password' => null, 'email_verified_at' => now(),
        ]);

        $token = 'plain-token-123';
        DB::table('ol_customer_password_reset_tokens')->insert([
            'email' => 'g@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->postJson('/api/reset-password', [
            'email' => 'g@example.com',
            'token' => $token,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $customer->refresh();
        $this->assertTrue(Hash::check('brand-new-pass', $customer->password));

        $this->postJson('/api/login', [
            'email' => 'g@example.com', 'password' => 'brand-new-pass',
        ])->assertOk();
    }

    public function test_reset_password_rejects_expired_token(): void
    {
        OlCustomer::create([
            'name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('password1'),
        ]);
        DB::table('ol_customer_password_reset_tokens')->insert([
            'email' => 'a@example.com',
            'token' => Hash::make('tk'),
            'created_at' => now()->subMinutes(61),
        ]);

        $this->postJson('/api/reset-password', [
            'email' => 'a@example.com',
            'token' => 'tk',
            'password' => 'newpass12',
            'password_confirmation' => 'newpass12',
        ])->assertStatus(422)->assertJsonPath('error_code', 'invalid_token');
    }

    public function test_verify_email_marks_verified(): void
    {
        $customer = OlCustomer::create([
            'name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('password1'), 'email_verified_at' => null,
        ]);
        DB::table('ol_customer_email_verification_tokens')->insert([
            'email' => 'a@example.com',
            'token' => Hash::make('vtk'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/verify-email', ['email' => 'a@example.com', 'token' => 'vtk'])->assertOk();

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
    }

    public function test_verify_email_rejects_expired_token(): void
    {
        OlCustomer::create([
            'name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('password1'), 'email_verified_at' => null,
        ]);
        DB::table('ol_customer_email_verification_tokens')->insert([
            'email' => 'a@example.com',
            'token' => Hash::make('vtk'),
            'created_at' => now()->subHours(25),
        ]);

        $this->postJson('/api/verify-email', ['email' => 'a@example.com', 'token' => 'vtk'])
            ->assertStatus(422)->assertJsonPath('error_code', 'invalid_token');
    }

    public function test_set_password_without_current_for_oauth_only(): void
    {
        $customer = OlCustomer::create([
            'name' => 'G', 'email' => 'g@example.com', 'password' => null, 'email_verified_at' => now(),
        ]);
        $token = $customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profile/password', [
                'new_password' => 'new-secret1',
                'new_password_confirmation' => 'new-secret1',
            ])->assertOk();

        $customer->refresh();
        $this->assertTrue(Hash::check('new-secret1', $customer->password));
    }

    public function test_unlink_last_login_method_is_refused(): void
    {
        $customer = OlCustomer::create([
            'name' => 'G', 'email' => 'g@example.com', 'password' => null, 'email_verified_at' => now(),
        ]);
        $customer->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'gid', 'provider_email' => 'g@example.com',
        ]);
        $token = $customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/profile/linked-accounts/google')
            ->assertStatus(422)->assertJsonPath('error_code', 'last_login_method');

        $this->assertDatabaseHas('ol_customer_social_accounts', [
            'customer_id' => $customer->id, 'provider' => 'google',
        ]);
    }

    public function test_unlink_allowed_when_password_exists(): void
    {
        $customer = OlCustomer::create([
            'name' => 'G', 'email' => 'g@example.com', 'password' => Hash::make('password1'), 'email_verified_at' => now(),
        ]);
        $customer->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'gid', 'provider_email' => 'g@example.com',
        ]);
        $token = $customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/profile/linked-accounts/google')->assertOk();

        $this->assertDatabaseMissing('ol_customer_social_accounts', [
            'customer_id' => $customer->id, 'provider' => 'google',
        ]);
    }

    public function test_linked_accounts_reports_state(): void
    {
        $customer = OlCustomer::create([
            'name' => 'G', 'email' => 'g@example.com', 'password' => null, 'email_verified_at' => now(),
        ]);
        $customer->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'gid', 'provider_email' => 'g@example.com',
        ]);
        $token = $customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile/linked-accounts')
            ->assertOk()
            ->assertJsonPath('has_password', false)
            ->assertJsonPath('providers.0', 'google');
    }
}
