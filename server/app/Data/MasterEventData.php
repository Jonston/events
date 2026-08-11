<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Event;
use App\Models\EventSession;
use Spatie\LaravelData\Data;

/**
 * Мероприятие в кабинете мастера.
 *
 * Отличается от витринного: здесь видно черновики, счётчик заявок и сырые
 * значения полей — форма правит числа и даты, а не подписи к ним.
 */
class MasterEventData extends Data
{
    /**
     * @param  array<int, array{day: string, startsAt: string, endsAt: string|null, title: string, note: string|null}>  $sessions
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $summary,
        public ?string $description,
        public ?string $image,
        public string $startsAt,
        public ?string $endsAt,
        public ?string $location,
        public ?float $latitude,
        public ?float $longitude,
        public float $price,
        public string $priceFormatted,
        public ?float $depositRate,
        public ?int $capacity,
        public bool $isPublished,
        public int $leadsCount,
        public array $sessions,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            slug: $event->slug,
            title: $event->title,
            summary: $event->summary,
            description: $event->description,
            image: $event->imageUrl(),
            // Формат браузерного `datetime-local`: поле формы принимает
            // именно его, и приводить строку на клиенте — лишний шаг,
            // на котором теряется часовой пояс.
            startsAt: $event->starts_at->format('Y-m-d\TH:i'),
            endsAt: $event->ends_at?->format('Y-m-d\TH:i'),
            location: $event->location,
            latitude: $event->latitude === null ? null : (float) $event->latitude,
            longitude: $event->longitude === null ? null : (float) $event->longitude,
            price: (float) $event->price,
            priceFormatted: EventCardData::money((float) $event->price),
            depositRate: $event->deposit_rate,
            capacity: $event->capacity,
            isPublished: $event->is_published,
            leadsCount: (int) ($event->leads_count ?? 0),
            sessions: $event->sessions->map(fn (EventSession $session): array => [
                'day' => $session->day->toDateString(),
                'startsAt' => substr((string) $session->starts_at, 0, 5),
                'endsAt' => $session->ends_at === null ? null : substr((string) $session->ends_at, 0, 5),
                'title' => $session->title,
                'note' => $session->note,
            ])->all(),
        );
    }
}
