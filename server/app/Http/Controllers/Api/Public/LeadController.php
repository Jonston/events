<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Models\Event;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $eventId = $request->integer('eventId') ?: null;
        $masterId = $request->integer('masterId') ?: null;

        // Заявка с мероприятия принадлежит и его мастеру: без этого лид
        // с карточки события не попал бы в статистику того, кто его ведёт.
        if ($masterId === null && $eventId !== null) {
            $masterId = Event::whereKey($eventId)->value('master_id');
        }

        $lead = Lead::create([
            'name' => $request->string('name')->value(),
            'phone' => $request->string('phone')->value(),
            'email' => $request->string('email')->value() ?: null,
            'message' => $request->string('message')->value() ?: null,
            'event_id' => $eventId,
            'master_id' => $masterId,
            'user_id' => $request->user()?->id,
            'source' => $eventId !== null ? 'event' : 'master',
            'client_ip' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'landing_url' => $request->header('Referer'),
        ]);

        return response()->json([
            'id' => $lead->id,
            'message' => 'Заявка отправлена — свяжемся с вами в течение суток.',
        ], 201);
    }
}
