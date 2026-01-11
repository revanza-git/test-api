<?php

namespace Tests\Feature;

use App\Mail\AccountCreatedMail;
use App\Mail\NewUserAdminNotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_and_returns_expected_payload(): void
    {
        Mail::fake();

        config()->set('mail.admin_address', 'admin@example.com');

        $userEmail = 'test@example.com';
        $adminEmail = (string) config('mail.admin_address');

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => $userEmail,
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'email',
                'name',
                'created_at',
            ])
            ->assertJsonMissing([
                'password',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userEmail,
            'name' => 'Test User',
        ]);

        Mail::assertSent(AccountCreatedMail::class, function (AccountCreatedMail $mail) use ($userEmail) {
            return $mail->hasTo($userEmail);
        });

        Mail::assertSent(NewUserAdminNotificationMail::class, function (NewUserAdminNotificationMail $mail) use ($adminEmail) {
            return $mail->hasTo($adminEmail);
        });
    }
}
