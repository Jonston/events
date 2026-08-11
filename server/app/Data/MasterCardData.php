<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Master;
use Spatie\LaravelData\Data;

/**
 * Мастер в списке: столько, сколько нужно карточке в каталоге.
 * Биография и расписание приезжают отдельно, на его собственной странице.
 */
class MasterCardData extends Data
{
    /**
     * @param  array{value: float, count: int}|null  $rating
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $headline,
        public ?string $city,
        public ?string $photo,
        public ?string $cover,
        public ?array $rating,
        public int $upcomingEventsCount,
    ) {}

    public static function fromModel(Master $master): self
    {
        return new self(
            id: $master->id,
            slug: $master->slug,
            name: $master->name,
            headline: $master->headline,
            city: $master->city,
            photo: $master->photoUrl(),
            cover: $master->coverUrl(),
            rating: $master->ratingSummary(),
            // events_count приходит из withCount на списках; на одиночной
            // странице считать нечего — там мероприятия и так загружены.
            upcomingEventsCount: (int) ($master->events_count ?? $master->events->count()),
        );
    }
}
