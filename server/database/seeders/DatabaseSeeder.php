<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Учётные записи площадки: один администратор, пять мастеров,
     * десять участников.
     *
     * Три адреса названы по роли — под ними заходят руками при разработке,
     * и угадывать, кем ты вошёл, не приходится. Остальные со случайными
     * адресами: на них видно, как списки ведут себя с настоящими данными,
     * а не с тремя ровными строчками.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $this->account('admin@example.com', 'Администратор площадки', UserRole::Admin);
        $this->account('master@example.com', 'Мастер Иванов', UserRole::Master);
        $this->account('user@example.com', 'Участник Петров', UserRole::Member);

        User::factory()
            ->count(4)
            ->create()
            ->each(fn (User $user) => $user->syncRoles([UserRole::Master->value]));

        User::factory()
            ->count(9)
            ->create()
            ->each(fn (User $user) => $user->syncRoles([UserRole::Member->value]));

        // После участников: отзывы на витрине пишут они, и без них сеятелю
        // витрины было бы некого назначить авторами.
        $this->call(ShowcaseSeeder::class);
    }

    /** Именной аккаунт с известным паролем — для входа руками. */
    private function account(string $email, string $name, UserRole $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role->value]);

        return $user;
    }
}
