<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Master;
use Spatie\LaravelData\Data;

class MasterProfileData extends Data
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $headline,
        public ?string $bio,
        public ?string $city,
        public ?string $photo,
        public ?string $cover,
        public bool $isActive,
        /** Адрес витрины — чтобы из кабинета можно было посмотреть, как вышло. */
        public string $publicUrl,
    ) {}

    public static function fromModel(Master $master): self
    {
        return new self(
            id: $master->id,
            slug: $master->slug,
            name: $master->name,
            headline: $master->headline,
            bio: $master->bio,
            city: $master->city,
            photo: $master->photoUrl(),
            cover: $master->coverUrl(),
            isActive: $master->is_active,
            publicUrl: '/masters/'.$master->slug,
        );
    }
}
