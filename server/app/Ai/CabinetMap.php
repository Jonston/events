<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Карта кабинета: разделы, по которым помощник имеет право водить человека.
 *
 * Список один на двоих — из него собирается и описание разделов в инструкции
 * агента, и белый список для инструмента перехода. Держи их порознь, и модель
 * рано или поздно предложит адрес, которого нет, либо тот, что закрыт ролью:
 * страница мастера у участника заканчивается разворотом на «нет доступа».
 */
final class CabinetMap
{
    /**
     * Раздел без роли открыт любому вошедшему.
     *
     * @var array<int, array{key: string, path: string, label: string, hint: string, role: string|null}>
     */
    private const SECTIONS = [
        [
            'key' => 'cabinet',
            'path' => '/cabinet',
            'label' => 'Обзор кабинета',
            'hint' => 'Личный кабинет участника: записи, отзывы, заявка на свою витрину.',
            'role' => null,
        ],
        [
            'key' => 'cabinet_profile',
            'path' => '/cabinet/profile',
            'label' => 'Мои данные',
            'hint' => 'Имя и телефоны участника. Здесь же выбирается основной номер для заявок.',
            'role' => null,
        ],
        [
            'key' => 'events',
            'path' => '/events',
            'label' => 'Витрина мероприятий',
            'hint' => 'Общий каталог мероприятий площадки, открыт всем.',
            'role' => null,
        ],
        [
            'key' => 'masters',
            'path' => '/masters',
            'label' => 'Витрина мастеров',
            'hint' => 'Общий каталог мастеров площадки, открыт всем.',
            'role' => null,
        ],
        [
            'key' => 'master_overview',
            'path' => '/master',
            'label' => 'Обзор мастера',
            'hint' => 'Сводка кабинета мастера: ближайшие мероприятия и свежие заявки.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'master_profile',
            'path' => '/master/profile',
            'label' => 'Моя страница',
            'hint' => 'Витрина мастера: описание, обложка, адрес и контакты.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'master_events',
            'path' => '/master/events',
            'label' => 'Мои мероприятия',
            'hint' => 'Список мероприятий мастера: правка, публикация, расписание.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'master_event_create',
            'path' => '/master/events/create',
            'label' => 'Новое мероприятие',
            'hint' => 'Пустая форма мероприятия. Сюда ведут, когда человек хочет создать новое.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'master_leads',
            'path' => '/master/leads',
            'label' => 'Заявки',
            'hint' => 'Заявки участников на мероприятия мастера и их статусы.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'master_reviews',
            'path' => '/master/reviews',
            'label' => 'Отзывы',
            'hint' => 'Отзывы, которые участники оставили мастеру.',
            'role' => UserRole::Master->value,
        ],
        [
            'key' => 'admin_overview',
            'path' => '/admin',
            'label' => 'Обзор площадки',
            'hint' => 'Панель администратора: сводка по площадке.',
            'role' => UserRole::Admin->value,
        ],
        [
            'key' => 'admin_users',
            'path' => '/admin/users',
            'label' => 'Пользователи',
            'hint' => 'Список людей площадки с ролями и контактами.',
            'role' => UserRole::Admin->value,
        ],
    ];

    /**
     * Разделы, доступные этому человеку.
     *
     * Роль проверяется точным совпадением, как и на клиенте: администратор
     * без роли мастера в кабинет мастера не попадёт, и предлагать ему туда
     * дорогу — значит обещать несбыточное.
     *
     * @return Collection<int, array{key: string, path: string, label: string, hint: string, role: string|null}>
     */
    public static function forUser(User $user): Collection
    {
        return collect(self::SECTIONS)
            ->filter(fn (array $section): bool => $section['role'] === null || $user->hasRole($section['role']))
            ->values();
    }

    /**
     * Раздел по ключу — или null, если такого нет либо он закрыт ролью.
     *
     * @return array{key: string, path: string, label: string, hint: string, role: string|null}|null
     */
    public static function find(User $user, string $key): ?array
    {
        return self::forUser($user)->firstWhere('key', $key);
    }

    /**
     * Ключи разделов для схемы инструмента.
     *
     * @return array<int, string>
     */
    public static function keysFor(User $user): array
    {
        return self::forUser($user)->pluck('key')->all();
    }

    /** Описание разделов для инструкции агента: ключ, адрес и зачем туда идут. */
    public static function describeFor(User $user): string
    {
        return self::forUser($user)
            ->map(fn (array $section): string => sprintf(
                '- %s (%s) — «%s». %s',
                $section['key'],
                $section['path'],
                $section['label'],
                $section['hint'],
            ))
            ->join("\n");
    }
}
