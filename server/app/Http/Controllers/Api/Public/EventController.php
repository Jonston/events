<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Data\EventCardData;
use App\Data\EventDetailData;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->published()
            ->upcoming()
            ->with('master')
            ->get();

        return response()->json([
            'data' => $events->map(EventCardData::fromModel(...)),
        ]);
    }

    public function show(Event $event): EventDetailData
    {
        abort_unless($event->is_published, 404);

        $event->load(['master', 'sessions']);

        return EventDetailData::fromModel($event);
    }
}
