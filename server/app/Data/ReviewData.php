<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Review;
use Spatie\LaravelData\Data;

class ReviewData extends Data
{
    public function __construct(
        public int $id,
        public string $author,
        public int $rating,
        public ?string $body,
        public string $createdAt,
    ) {}

    public static function fromModel(Review $review): self
    {
        return new self(
            id: $review->id,
            // Только имя: фамилию и почту человек оставлял площадке,
            // а не читателям страницы мастера.
            author: $review->user?->name ?? 'Участник',
            rating: $review->rating,
            body: $review->body,
            createdAt: $review->created_at->toIso8601String(),
        );
    }
}
