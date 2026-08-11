<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Data\EventCardData;
use App\Data\MasterCardData;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Master;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Всё, что нужно главной, одним запросом: мастера и ближайшие даты.
 * Двумя запросами страница ждала бы дважды ради одного экрана.
 */
class HomeController extends Controller
{
    private const MASTERS_LIMIT = 6;

    private const EVENTS_LIMIT = 4;

    public function __invoke(): JsonResponse
    {
        $masters = Master::query()
            ->active()
            ->ordered()
            ->withCount(['events' => fn (Builder $query) => $query->published()->upcoming()])
            ->withCount(['reviews' => fn (Builder $query) => $query->published()])
            ->withAvg(['reviews' => fn (Builder $query) => $query->published()], 'rating')
            ->limit(self::MASTERS_LIMIT)
            ->get();

        $events = Event::query()
            ->published()
            ->upcoming()
            ->with('master')
            ->limit(self::EVENTS_LIMIT)
            ->get();

        return response()->json([
            'masters' => $masters->map(MasterCardData::fromModel(...)),
            'events' => $events->map(EventCardData::fromModel(...)),
        ]);
    }
}
