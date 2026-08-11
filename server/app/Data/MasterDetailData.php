<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Master;
use Spatie\LaravelData\Data;

class MasterDetailData extends Data
{
    /**
     * @param  array<int, EventCardData>  $events
     * @param  array<int, ReviewData>  $reviews
     */
    public function __construct(
        public MasterCardData $card,
        public ?string $bio,
        public array $events,
        public array $reviews,
    ) {}

    public static function fromModel(Master $master): self
    {
        return new self(
            card: MasterCardData::fromModel($master),
            bio: $master->bio,
            events: $master->events->map(EventCardData::fromModel(...))->all(),
            reviews: $master->reviews->map(ReviewData::fromModel(...))->all(),
        );
    }
}
