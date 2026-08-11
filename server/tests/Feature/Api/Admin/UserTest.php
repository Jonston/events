<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole(UserRole::Admin->value);
    }

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_list_is_closed_to_non_admins(): void
    {
        $member = User::factory()->create()->assignRole(UserRole::Member->value);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_list_is_paginated(): void
    {
        User::factory()->count(5)->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/users?perPage=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('meta.perPage', 2);
    }

    public function test_search_filters_by_name(): void
    {
        User::factory()->create(['name' => 'Ирина Соколова']);
        User::factory()->create(['name' => 'Пётр Кузнецов']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/users?search=Соколова')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ирина Соколова');
    }

    public function test_page_size_is_capped(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/users?perPage=100000')
            ->assertOk()
            ->assertJsonPath('meta.perPage', 100);
    }

    public function test_unknown_sort_column_is_ignored(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/users?sort=password')
            ->assertOk();
    }
}
