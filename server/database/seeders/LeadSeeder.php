<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Заявки к мероприятиям — чтобы кабинет мастера не открывался пустым.
 *
 * Статусы разные и с историей: пустая таблица не показывает ни фильтров,
 * ни цветов, ни того, ради чего заведена история.
 */
class LeadSeeder extends Seeder
{
    /** Что пишут в заявке — вперемешку с молчунами: так и бывает. */
    private const MESSAGES = [
        'Хочу разобраться с зажимом в плече, давно собираюсь.',
        'Приду с подругой, можно вдвоём?',
        null,
        'Первый раз на такой практике, немного волнуюсь.',
        null,
        'Работаю до 18, успею к началу?',
    ];

    public function run(): void
    {
        $members = User::role(UserRole::Member->value)->with('phones')->get();
        $master = User::role(UserRole::Master->value)->whereHas('master')->first();

        if ($members->isEmpty() || $master?->master === null) {
            return;
        }

        $events = $master->master->events()->orderBy('starts_at')->take(4)->get();

        foreach ($events as $index => $event) {
            // `values()` обязателен: `slice()` сохраняет исходные ключи,
            // и позиция внутри мероприятия перестала бы начинаться с нуля.
            foreach ($members->slice($index * 2, 3)->values() as $position => $member) {
                $this->lead($event, $member, $position, $index);
            }
        }
    }

    private function lead(Event $event, User $member, int $position, int $eventIndex): void
    {
        $lead = Lead::create([
            'master_id' => $event->master_id,
            'event_id' => $event->id,
            'user_id' => $member->id,
            'name' => $member->name,
            'phone' => $member->primaryPhone(),
            'email' => $member->email,
            'message' => self::MESSAGES[($eventIndex * 3 + $position) % count(self::MESSAGES)],
            'source' => 'event',
            'created_at' => now()->subDays(($eventIndex * 3 + $position) % 12 + 1),
        ]);

        // Первая заявка у каждого мероприятия — бронь, вторая тёплая,
        // третья так и лежит новой.
        $status = match ($position) {
            0 => LeadStatus::Booked,
            1 => LeadStatus::Warm,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $lead->update(['status' => $status]);

        $lead->statusChanges()->create([
            'user_id' => $event->master->user_id,
            'from_status' => LeadStatus::New,
            'to_status' => $status,
            'comment' => $status === LeadStatus::Booked
                ? 'Созвонились, предоплату внесли.'
                : 'Написал в мессенджер, ждёт ответа от коллеги.',
            'created_at' => $lead->created_at->addHours(6),
        ]);
    }
}
