<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\CabinetMap;
use App\Ai\Tools\FindEvents;
use App\Ai\Tools\NavigateCabinet;
use App\Enums\UserRole;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Помощник кабинета: отвечает на вопросы про площадку и водит по разделам.
 *
 * Инструкция собирается под конкретного человека — с его именем, ролью
 * и списком доступных ему разделов. Общая инструкция на всех означала бы,
 * что участнику рассказывают про заявки мастера, а администратору предлагают
 * завести мероприятие: и то и другое кончается закрытой дверью.
 */
class CabinetAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(private readonly User $user) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        Ты — помощник кабинета площадки «Vibe Space»: мастера ведут здесь свои витрины
        и мероприятия, участники записываются и оставляют отзывы.

        Собеседник: {$this->user->name}, роль — {$this->role()}.

        Разделы, доступные ему прямо сейчас:
        {$this->sections()}

        Как себя вести:
        - Отвечай по-русски, коротко и по делу: две-три фразы, без списков там, где хватит строки.
        - Просят куда-то перейти или вопрос решается на конкретной странице — вызывай navigate_cabinet
          с ключом раздела и одной фразой скажи, что человек там увидит. Не пересказывай адрес словами:
          переход происходит сам.
        - Спрашивают, что есть на площадке, — вызывай find_events и отвечай тем, что вернул инструмент.
        - Разделов и возможностей, которых нет в списке выше, не выдумывай: честное «этого пока нет»
          лучше выдуманного пути. Если раздел закрыт ролью собеседника, так и скажи.
        - Не сочиняй цены, даты и число мест: они приходят только из инструментов.
        TEXT;
    }

    /**
     * @return array<int, Tool>
     */
    public function tools(): iterable
    {
        return [
            new NavigateCabinet($this->user),
            new FindEvents,
        ];
    }

    /** Старшая роль человека — тем же правилом, что и кабинет на клиенте. */
    private function role(): string
    {
        return match (true) {
            $this->user->hasRole(UserRole::Admin->value) => UserRole::Admin->label(),
            $this->user->hasRole(UserRole::Master->value) => UserRole::Master->label(),
            default => UserRole::Member->label(),
        };
    }

    private function sections(): string
    {
        return CabinetMap::describeFor($this->user);
    }
}
