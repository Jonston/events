<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Мероприятие принадлежит витрине мастера, а не площадке.
 *
 * Право проверяется по владельцу профиля, а не по роли: у роли «мастер»
 * может не быть своей витрины — тогда ему и править нечего. Администратор
 * отвечает за каталог целиком и правит чужое.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->master !== null;
    }

    public function view(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    public function create(User $user): bool
    {
        // Администратору тоже нужна витрина, к которой мероприятие можно
        // привязать: у мероприятия без мастера нет автора и нет страницы.
        return $user->master !== null;
    }

    public function update(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    private function owns(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->master !== null && $event->master_id === $user->master->id;
    }
}
