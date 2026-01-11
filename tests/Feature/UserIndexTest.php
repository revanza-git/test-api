<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_401_without_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_it_returns_paginated_active_users_with_orders_count_and_can_edit(): void
    {
        $actor = User::factory()->create([
            'role' => 'administrator',
            'active' => true,
        ]);

        Sanctum::actingAs($actor);

        $activeUser = User::factory()->create([
            'role' => 'user',
            'active' => true,
        ]);
        Order::factory()->create(['user_id' => $activeUser->id]);

        User::factory()->create([
            'role' => 'user',
            'active' => false,
        ]);

        $response = $this->getJson('/api/users');

        // Helpful when diagnosing unexpected response shapes.
        // $response->dump();

        $response
            ->assertOk()
            ->assertJsonStructure([
                'page',
                'users' => [
                    '*' => [
                        'id',
                        'email',
                        'name',
                        'role',
                        'created_at',
                        'orders_count',
                        'can_edit',
                    ],
                ],
            ]);

        $users = $response->json('users');

        $activePayload = collect($users)->firstWhere('id', $activeUser->id);
        $this->assertSame(1, $activePayload['orders_count']);
        $this->assertTrue($activePayload['can_edit']);
    }

    public function test_it_supports_search_by_name_or_email(): void
    {
        $actor = User::factory()->create(['role' => 'administrator']);
        Sanctum::actingAs($actor);

        $needleByName = User::factory()->create([
            'name' => 'John Needle',
            'email' => 'john.needle@example.com',
            'active' => true,
        ]);
        $needleByEmail = User::factory()->create([
            'name' => 'Jane Haystack',
            'email' => 'needle-jane@example.com',
            'active' => true,
        ]);

        User::factory()->create([
            'name' => 'Other Person',
            'email' => 'other@example.com',
            'active' => true,
        ]);

        $byName = $this->getJson('/api/users?search=Needle')->assertOk();
        $idsByName = collect($byName->json('users'))->pluck('id')->all();
        $this->assertContains($needleByName->id, $idsByName);

        $byEmail = $this->getJson('/api/users?search=needle-jane')->assertOk();
        $idsByEmail = collect($byEmail->json('users'))->pluck('id')->all();
        $this->assertContains($needleByEmail->id, $idsByEmail);
    }

    public function test_it_allows_sort_by_allowlist_and_defaults_to_created_at(): void
    {
        $actor = User::factory()->create(['role' => 'administrator']);
        Sanctum::actingAs($actor);

        $older = User::factory()->create([
            'name' => 'B User',
            'email' => 'b@example.com',
            'active' => true,
            'created_at' => Carbon::parse('2026-01-01 00:00:00'),
        ]);
        $newer = User::factory()->create([
            'name' => 'A User',
            'email' => 'a@example.com',
            'active' => true,
            'created_at' => Carbon::parse('2026-01-02 00:00:00'),
        ]);

        // Default sort: created_at
        $default = $this->getJson('/api/users')->assertOk();
        $ids = collect($default->json('users'))->pluck('id')->all();
        $this->assertSame($older->id, $ids[0]);

        // Allowlisted sort: name
        $byName = $this->getJson('/api/users?sortBy=name')->assertOk();
        $idsByName = collect($byName->json('users'))->pluck('id')->all();
        $this->assertSame($newer->id, $idsByName[0]);

        // Invalid sort falls back to created_at
        $invalid = $this->getJson('/api/users?sortBy=invalid')->assertOk();
        $idsInvalid = collect($invalid->json('users'))->pluck('id')->all();
        $this->assertSame($older->id, $idsInvalid[0]);
    }
}
