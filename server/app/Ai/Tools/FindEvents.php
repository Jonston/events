<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Data\EventCardData;
use App\Enums\LeadStatus;
use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Поиск по витрине мероприятий.
 *
 * Без него помощник знал бы только устройство кабинета и на «что есть
 * по керамике в сентябре» отвечал бы догадками. Отдаёт только опубликованные
 * и ещё не начавшиеся мероприятия — то же, что человек увидел бы на витрине.
 */
final class FindEvents implements Tool
{
    /** Больше пяти модель всё равно не перескажет, а контекст съест. */
    private const LIMIT = 5;

    public function name(): string
    {
        return 'find_events';
    }

    public function description(): Stringable|string
    {
        return 'Ищет ближайшие опубликованные мероприятия площадки по названию, описанию или городу. '
            .'Вызывай, когда человек спрашивает, что есть на площадке, чем заняться или сколько стоит участие.';
    }

    public function handle(Request $request): Stringable|string
    {
        $term = trim((string) ($request['query'] ?? ''));

        $events = Event::query()
            ->published()
            ->upcoming()
            ->with('master')
            // Свободные места считаются одним запросом на всю выборку:
            // без этого каждая строка списка ходила бы в базу за своими бронями.
            ->withCount(['leads as leads_booked_count' => fn (Builder $query) => $query->whereIn('status', LeadStatus::seatHolding())])
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.$term.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhere('summary', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            })
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Event $event): array => [
                'title' => $event->title,
                'master' => $event->master?->name,
                'starts_at' => $event->starts_at->translatedFormat('j F Y, H:i'),
                'location' => $event->location,
                'price' => EventCardData::money((float) $event->price),
                'seats_left' => $event->seatsLeft(),
                'url' => '/events/'.$event->slug,
            ]);

        if ($events->isEmpty()) {
            return 'Ничего не нашлось. Так и скажи и предложи посмотреть витрину целиком.';
        }

        return (string) json_encode($events->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Слово или два из запроса человека: тема, город, имя мастера. Пусто — просто ближайшие мероприятия.'),
        ];
    }
}
