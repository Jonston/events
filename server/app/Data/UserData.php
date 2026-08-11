<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

/**
 * Пользователь в том виде, в каком его получает клиент.
 *
 * Ключи — camelCase: их читает TypeScript, и переименование на границе
 * избавляет клиент от snake_case посреди типизированного кода.
 */
class UserData extends Data
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $avatarUrl,
        public array $roles,
        public bool $emailVerified,
        public string $createdAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            avatarUrl: $user->avatar_url,
            roles: $user->roles->pluck('name')->all(),
            emailVerified: $user->email_verified_at !== null,
            createdAt: $user->created_at->toIso8601String(),
        );
    }
}
