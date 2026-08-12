<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Кабинет участника: свои данные и телефоны. */
class CabinetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    private function member(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Member->value);

        return $user->refresh();
    }

    public function test_cabinet_is_closed_to_guests(): void
    {
        $this->getJson('/api/cabinet/profile')->assertUnauthorized();
    }

    public function test_member_saves_name_and_phones(): void
    {
        $member = $this->member();

        $this->actingAs($member, 'sanctum')
            ->putJson('/api/cabinet/profile', [
                'name' => 'Пётр Петров',
                'phones' => [
                    ['phone' => '+380671112233', 'isPrimary' => false],
                    ['phone' => '+380509998877', 'isPrimary' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Пётр Петров')
            // Основной номер идёт первым и отдельным полем: форма заявки
            // подставляет именно его.
            ->assertJsonPath('phone', '+380509998877')
            ->assertJsonPath('phones.0', '+380509998877')
            ->assertJsonPath('phones.1', '+380671112233');
    }

    public function test_saved_list_replaces_the_previous_one(): void
    {
        $member = $this->member();

        $this->actingAs($member, 'sanctum')->putJson('/api/cabinet/profile', [
            'name' => $member->name,
            'phones' => [['phone' => '+380671112233', 'isPrimary' => true]],
        ])->assertOk();

        $this->actingAs($member, 'sanctum')->putJson('/api/cabinet/profile', [
            'name' => $member->name,
            'phones' => [['phone' => '+380509998877', 'isPrimary' => true]],
        ])->assertOk()->assertJsonCount(1, 'phones');

        $this->assertSame(['+380509998877'], $member->refresh()->phones->pluck('phone')->all());
    }

    public function test_first_phone_becomes_primary_when_none_is_marked(): void
    {
        $member = $this->member();

        $this->actingAs($member, 'sanctum')->putJson('/api/cabinet/profile', [
            'name' => $member->name,
            'phones' => [['phone' => '+380671112233'], ['phone' => '+380509998877']],
        ])->assertOk()->assertJsonPath('phone', '+380671112233');
    }

    public function test_the_same_number_twice_is_a_typo(): void
    {
        $member = $this->member();

        $this->actingAs($member, 'sanctum')->putJson('/api/cabinet/profile', [
            'name' => $member->name,
            'phones' => [['phone' => '+380671112233'], ['phone' => '+380671112233']],
        ])->assertStatus(422)->assertJsonValidationErrors('phones.0.phone');
    }

    public function test_phones_come_with_the_account(): void
    {
        $member = $this->member();

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonCount(1, 'phones');
    }
}
