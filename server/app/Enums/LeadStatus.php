<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Путь заявки от контакта до состоявшегося участия.
 *
 * Отказ — тоже конец пути, а не отсутствие движения: мастер должен видеть,
 * что заявку отработали, а не что до неё не дошли руки.
 */
enum LeadStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Booked = 'booked';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InProgress => 'В работе',
            self::Booked => 'Забронировано',
            self::Completed => 'Состоялось',
            self::Rejected => 'Отказ',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
