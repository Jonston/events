<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Сводка кабинета мастера.
 *
 * Считаем ровно то, на что человек может отреагировать сегодня: новые
 * заявки, ближайшая дата, черновики. «Всего мероприятий за всё время» —
 * красивое число, по которому нечего сделать.
 */
class OverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $master = $request->user()->master;

        if ($master === null) {
            return response()->json(['hasProfile' => false]);
        }

        $nextEvent = $master->events()->published()->upcoming()->first();

        return response()->json([
            'hasProfile' => true,
            'isActive' => $master->is_active,
            'publicUrl' => '/masters/'.$master->slug,
            'published' => $master->events()->published()->upcoming()->count(),
            'drafts' => $master->events()->where('is_published', false)->count(),
            'newLeads' => $master->leads()->where('status', LeadStatus::New)->count(),
            'totalLeads' => $master->leads()->count(),
            'rating' => $master->ratingSummary(),
            'nextEvent' => $nextEvent === null ? null : [
                'title' => $nextEvent->title,
                'slug' => $nextEvent->slug,
                'startsAt' => $nextEvent->starts_at->toIso8601String(),
                'leadsCount' => $nextEvent->leads()->count(),
                'capacity' => $nextEvent->capacity,
            ],
        ]);
    }
}
