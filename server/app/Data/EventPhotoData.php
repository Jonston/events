<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Event;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Снимок из галереи мероприятия.
 *
 * Два адреса, а не один: в сетке нужен лёгкий квадрат, в полноэкранном
 * просмотре — кадр целиком. Одна картинка на оба случая означала бы либо
 * мыло на весь экран, либо десяток тяжёлых снимков ради плиток.
 */
class EventPhotoData extends Data
{
    public function __construct(
        public int $id,
        public string $thumb,
        public string $full,
    ) {}

    public static function fromMedia(Media $media): self
    {
        return new self(
            id: $media->id,
            thumb: $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            full: $media->hasGeneratedConversion('card') ? $media->getUrl('card') : $media->getUrl(),
        );
    }

    /**
     * Галерея мероприятия в том порядке, в каком её собрал мастер.
     *
     * @return array<int, self>
     */
    public static function galleryOf(Event $event): array
    {
        return $event->getMedia('gallery')->map(self::fromMedia(...))->all();
    }
}
