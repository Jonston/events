<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Master;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сохранение мероприятия вместе с программой.
 *
 * Вынесено из контроллера, потому что создание и правка делают одно и то же
 * и разошлись бы при первой же новой колонке. Программа переписывается
 * целиком в одной транзакции: половина сохранённого расписания хуже,
 * чем несохранённое.
 */
class EventWriter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Event $event, Master $master, array $data, ?UploadedFile $image = null): Event
    {
        return DB::transaction(function () use ($event, $master, $data, $image): Event {
            $event->fill([
                'master_id' => $master->id,
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'starts_at' => $data['startsAt'],
                'ends_at' => $data['endsAt'] ?? null,
                'price' => $data['price'],
                'deposit_rate' => $data['depositRate'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'is_published' => (bool) ($data['isPublished'] ?? false),
            ]);

            // Адрес страницы закрепляется за мероприятием при создании
            // и дальше не меняется: правка названия не должна ломать ссылку,
            // которую человек уже кому-то отправил.
            $event->slug ??= $this->uniqueSlug($data['title']);

            $event->save();

            // Обложка — после сохранения: media library привязывает файл
            // к записи по id, а у новой записи его до этого нет.
            $event->syncImage('image', $image, ($data['removeImage'] ?? false) === true);

            $this->syncSessions($event, $data['sessions'] ?? []);

            return $event;
        });
    }

    /** Обложку уносить отдельно не нужно: media library убирает файлы за моделью. */
    public function delete(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->delete();
        });
    }

    /**
     * Программа переписывается целиком, а не сверяется построчно: у пунктов
     * нет устойчивого имени, по которому их можно было бы сопоставить,
     * и «обновление» свелось бы к тем же удалению и вставке, только длиннее.
     *
     * @param  array<int, array<string, mixed>>  $sessions
     */
    private function syncSessions(Event $event, array $sessions): void
    {
        $event->sessions()->delete();

        foreach ($sessions as $session) {
            $event->sessions()->create([
                'day' => $session['day'],
                'starts_at' => $session['startsAt'],
                'ends_at' => $session['endsAt'] ?? null,
                'title' => $session['title'],
                'note' => $session['note'] ?? null,
            ]);
        }
    }

    /**
     * Русское название транслитерируется, а совпадения разводятся суффиксом:
     * два «Дыхание выходного дня» в разные месяцы — обычное дело.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title, language: 'ru') ?: 'event';
        $slug = $base;
        $suffix = 2;

        while (Event::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
