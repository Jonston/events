<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Event;
use Illuminate\Support\Number;
use Spatie\LaravelData\Data;

/**
 * Мероприятие в списке.
 *
 * Цена приходит уже отформатированной: она показывается одинаково на всех
 * экранах, и собирать строку из числа и валюты в каждом компоненте — способ
 * получить три разных написания одной суммы.
 */
class EventCardData extends Data
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $summary,
        public ?string $image,
        public string $startsAt,
        public ?string $endsAt,
        public ?string $location,
        /** Точка на карте; null — мастер её не ставил. */
        public ?float $lat,
        public ?float $lng,
        public float $price,
        public string $priceFormatted,
        public ?int $masterId,
        public ?string $masterName,
        public ?string $masterSlug,
        /** Портрет ведущего: карточка мероприятия показывает его так же, как карточка мастера. */
        public ?string $masterPhoto,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            slug: $event->slug,
            title: $event->title,
            summary: $event->summary,
            image: $event->imageUrl(),
            startsAt: $event->starts_at->toIso8601String(),
            endsAt: $event->ends_at?->toIso8601String(),
            location: $event->location,
            lat: $event->latitude === null ? null : (float) $event->latitude,
            lng: $event->longitude === null ? null : (float) $event->longitude,
            price: (float) $event->price,
            priceFormatted: self::money((float) $event->price),
            masterId: $event->master?->id,
            masterName: $event->master?->name,
            masterSlug: $event->master?->slug,
            masterPhoto: $event->master?->photoUrl(),
        );
    }

    /**
     * Копейки показываем, только если они есть: «6 800 ₴» читается быстрее,
     * чем «6 800,00 ₴», а у круглых цен вторая половина строки — шум.
     */
    public static function money(float $amount): string
    {
        $precision = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return (string) Number::currency(
            $amount,
            in: config('app.currency'),
            locale: 'ru',
            precision: $precision,
        );
    }
}
