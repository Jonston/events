<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Роли площадки.
 *
 * Хранятся в таблице ролей spatie/laravel-permission, но перечислены здесь,
 * чтобы код не сверялся со строковыми литералами: опечатка в `'admin'`
 * молча выключила бы проверку доступа, а опечатка в имени кейса не соберётся.
 */
enum UserRole: string
{
    /** Ведёт площадку целиком: пользователи, мастера, мероприятия. */
    case Admin = 'admin';

    /** Ведёт свою витрину и свои мероприятия. */
    case Master = 'master';

    /** Обычный участник: покупает билеты, оставляет отзывы. */
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Master => 'Мастер',
            self::Member => 'Участник',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
