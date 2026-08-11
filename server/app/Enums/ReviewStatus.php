<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Состояние отзыва. На странице мастера видны только проверенные:
 * площадка отвечает за то, что написано о человеке на его же странице.
 */
enum ReviewStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'На проверке',
            self::Published => 'Опубликован',
            self::Rejected => 'Отклонён',
        };
    }
}
