<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Models\Event;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if ($eventId !== null && $this->alreadyApplied($request, $eventId)) {
            return response()->json([
                'message' => 'Вы уже оставляли заявку на это мероприятие — мастер с вами свяжется.',
                'applied' => true,
            ], 409);
        }

        $lead = Lead::create([
            'name' => $request->string('name')->value(),
            'phone' => $request->string('phone')->value(),
            'email' => $request->string('email')->value() ?: null,
            'message' => $request->string('message')->value() ?: null,
            'event_id' => $eventId,
            'master_id' => $masterId,
            // Гость и вошедший приходят одним адресом, поэтому охраны
            // на маршруте нет — токен разбираем сами: заявка от вошедшего
            // должна остаться связанной с его аккаунтом.
            'user_id' => $request->user('sanctum')?->id,
            'guest_key' => $request->string('guestKey')->value() ?: null,
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

    /**
     * Оставлял ли этот человек заявку на это мероприятие.
     *
     * Спрашивается при открытии страницы, чтобы показать не форму,
     * а отметку «вы уже записались»: узнать об этом после того, как всё
     * заполнил, — худший момент из возможных.
     */
    public function check(Request $request): JsonResponse
    {
        $eventId = $request->integer('eventId') ?: null;

        return response()->json([
            'applied' => $eventId !== null && $this->alreadyApplied($request, $eventId),
        ]);
    }

    /**
     * Узнаём человека тремя способами: по аккаунту, по метке его браузера
     * и по телефону.
     *
     * Телефон здесь не лишний: браузер он мог сменить, а номер в заявке
     * пишет тот же — по нему мастер и увидел бы две одинаковые записи.
     */
    private function alreadyApplied(Request $request, int $eventId): bool
    {
        $userId = $request->user('sanctum')?->id;
        $guestKey = $request->string('guestKey')->value() ?: null;
        $phone = $request->string('phone')->value() ?: null;

        if ($userId === null && $guestKey === null && $phone === null) {
            return false;
        }

        return Lead::query()
            ->where('event_id', $eventId)
            ->where(function (Builder $query) use ($userId, $guestKey, $phone): void {
                $query->when($userId !== null, fn (Builder $q) => $q->orWhere('user_id', $userId))
                    ->when($guestKey !== null, fn (Builder $q) => $q->orWhere('guest_key', $guestKey))
                    ->when($phone !== null, fn (Builder $q) => $q->orWhere('phone', $phone));
            })
            ->exists();
    }
}
