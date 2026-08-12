<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Event;
use App\Models\EventSession;
use Spatie\LaravelData\Data;

/**
 * Страница мероприятия: карточка плюс то, ради чего на неё заходят —
 * описание, программа по дням и полный расклад по деньгам.
 */
class EventDetailData extends Data
{
    /**
     * @param  array<int, array{day: string, items: array<int, array{time: string, title: string, note: string|null}>}>  $schedule
     * @param  array<int, EventPhotoData>  $gallery
     */
    public function __construct(
        public EventCardData $card,
        public ?string $description,
        public ?int $capacity,
        public array $schedule,
        public array $gallery,
        public string $depositFormatted,
        public string $remainingFormatted,
        public string $refundDeadline,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            card: EventCardData::fromModel($event),
            description: $event->description,
            capacity: $event->capacity,
            schedule: self::schedule($event),
            gallery: EventPhotoData::galleryOf($event),
            depositFormatted: EventCardData::money($event->depositAmount()),
            remainingFormatted: EventCardData::money($event->remainingAmount()),
            refundDeadline: $event->refundDeadline()->toIso8601String(),
        );
    }

    /**
     * Программа, сгруппированная по дням: экран показывает её днями,
     * и раскладывать плоский список на клиенте значит повторять эту
     * группировку в каждом потребителе API.
     *
     * @return array<int, array{day: string, items: array<int, array{time: string, title: string, note: string|null}>}>
     */
    private static function schedule(Event $event): array
    {
        return $event->sessions
            ->groupBy(fn (EventSession $session): string => $session->day->toDateString())
            ->map(fn ($sessions, string $day): array => [
                'day' => $day,
                'items' => $sessions->map(fn (EventSession $session): array => [
                    'time' => $session->timeRange(),
                    'title' => $session->title,
                    'note' => $session->note,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
