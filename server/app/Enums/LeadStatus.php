<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Что стало с заявкой.
 *
 * Набор короткий намеренно: каждое состояние что-то меняет для мастера
 * и для мероприятия. «В работе» и «состоялось» ничего не меняли, поэтому
 * их здесь нет — комментарий к заявке рассказывает об этом точнее, чем ещё
 * один статус в списке.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Warm = 'warm';
    case Booked = 'booked';
    case Refunded = 'refunded';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Warm => 'Тёплый',
            self::Booked => 'Бронь',
            self::Refunded => 'Возврат',
            self::Spam => 'Спам',
        };
    }

    /**
     * Цвет статуса приходит с сервера вместе с ним.
     *
     * Иначе список статусов задан в одном месте, а их вид — в другом,
     * и новый статус появляется на экране серым, пока кто-нибудь
     * не вспомнит про вторую таблицу соответствий.
     */
    public function color(): string
    {
        return match ($this) {
            // Синий — «поступила, ещё не в работе»: заметно, но без тревоги.
            self::New => 'info',
            // Оранжевый — разговор идёт, место пока не занято.
            self::Warm => 'warning',
            self::Booked => 'success',
            self::Refunded => 'error',
            self::Spam => 'neutral',
        };
    }

    /**
     * Занимает ли заявка место в группе.
     *
     * Правило живёт здесь, а не в подсчётах: свободные места считаются
     * в нескольких местах, и «какие статусы держат место» должно быть
     * записано один раз.
     */
    public function holdsSeat(): bool
    {
        return $this === self::Booked;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    /** @return array<int, string> */
    public static function seatHolding(): array
    {
        return array_values(array_map(
            static fn (self $status): string => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->holdsSeat()),
        ));
    }
}
