<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Data\MasterEventData;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventRequest;
use App\Models\Event;
use App\Models\Master;
use App\Services\EventWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Мероприятия мастера.
 *
 * Владелец берётся из аккаунта, а не из запроса: площадка агрегирует
 * мастеров, и мероприятие принадлежит витрине того, кто его публикует.
 * Выбирать автора в форме не из чего.
 */
class EventController extends Controller
{
    public function __construct(private readonly EventWriter $writer) {}

    public function index(Request $request): JsonResponse
    {
        $master = $this->master($request);

        $events = $master->events()
            ->withCount('leads')
            // Брони считаем тем же запросом: иначе каждая строка списка
            // лезла бы в базу за своими занятыми местами.
            ->withCount(['leads as leads_booked_count' => fn (Builder $query) => $query->whereIn('status', LeadStatus::seatHolding())])
            ->with(['sessions', 'media'])
            // Черновики и прошедшее тоже здесь: кабинет — рабочий стол,
            // а не витрина, и спрятать от мастера то, что он ещё не издал,
            // значило бы спрятать от него его же работу.
            ->orderByDesc('starts_at')
            ->get();

        return response()->json([
            'data' => $events->map(MasterEventData::fromModel(...)),
        ]);
    }

    public function show(Request $request, Event $event): MasterEventData
    {
        $this->authorize('update', $event);

        $event->load(['sessions', 'media'])->loadCount('leads');

        return MasterEventData::fromModel($event);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        $event = $this->writer->save(
            new Event,
            $this->master($request),
            $request->validated(),
            $request->file('image'),
            $request->gallery(),
        );

        return response()->json(
            MasterEventData::fromModel($event->load(['sessions', 'media'])),
            201,
        );
    }

    /**
     * Ответ собирается вручную: правка идёт POST-ом (в PUT браузер
     * не отправит файл обложки), а библиотека данных на любой POST
     * отвечает «201 Created» — для обновления это неправда.
     */
    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        // Владелец берётся у самого мероприятия, а не у того, кто правит:
        // иначе администратор, поправивший чужую опечатку, перевёз бы
        // мероприятие на свою витрину.
        $event = $this->writer->save(
            $event,
            $event->master,
            $request->validated(),
            $request->file('image'),
            $request->gallery(),
        );

        return response()->json(
            MasterEventData::fromModel($event->load(['sessions', 'media'])->loadCount('leads')),
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $this->writer->delete($event);

        return response()->json(['message' => 'Мероприятие удалено.']);
    }

    /**
     * Витрина текущего мастера. Без неё мероприятие не к чему привязать,
     * и это не ошибка запроса, а незаконченная настройка кабинета.
     */
    private function master(Request $request): Master
    {
        $master = $request->user()->master;

        abort_if($master === null, 409, 'Сначала заполните страницу мастера.');

        return $master;
    }
}
