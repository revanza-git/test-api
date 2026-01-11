<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_edit_anyone(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Sanctum::actingAs($admin);

        $target = User::factory()->create(['role' => 'manager']);

        $response = $this->getJson('/api/users');
        $response->assertOk();
        $payload = collect($response->json('users'))->firstWhere('id', $target->id);

        $this->assertTrue($payload['can_edit']);
    }

    public function test_manager_can_edit_only_users_with_role_user(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $user = User::factory()->create(['role' => 'user']);
        $otherManager = User::factory()->create(['role' => 'manager']);

        $response = $this->getJson('/api/users');
        $response->assertOk();

        $userPayload = collect($response->json('users'))->firstWhere('id', $user->id);
        $otherManagerPayload = collect($response->json('users'))->firstWhere('id', $otherManager->id);

        $this->assertTrue($userPayload['can_edit']);
        $this->assertFalse($otherManagerPayload['can_edit']);
    }

    public function test_user_can_edit_only_themselves(): void
    {
        $actor = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($actor);

        $other = User::factory()->create(['role' => 'user']);

        $response = $this->getJson('/api/users');
        $response->assertOk();

        $selfPayload = collect($response->json('users'))->firstWhere('id', $actor->id);
        $otherPayload = collect($response->json('users'))->firstWhere('id', $other->id);

        $this->assertTrue($selfPayload['can_edit']);
        $this->assertFalse($otherPayload['can_edit']);
    }
}
