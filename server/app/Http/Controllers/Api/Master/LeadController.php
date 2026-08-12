<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Data\MasterLeadData;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Master;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Заявки, пришедшие к этому мастеру.
 *
 * Список постраничный и с фильтрами: заявки копятся быстрее всего
 * остального в кабинете, и через месяц работы «просто список» становится
 * лентой, по которой ничего не найти.
 */
class LeadController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    /** По чему разрешено сортировать: имена колонок приходят из URL. */
    private const SORTABLE = ['created_at', 'name', 'status'];

    public function index(Request $request): JsonResponse
    {
        $master = $request->user()->master;

        if ($master === null) {
            return response()->json($this->emptyList());
        }

        $perPage = min(
            max((int) $request->integer('perPage', self::DEFAULT_PER_PAGE), 1),
            self::MAX_PER_PAGE,
        );

        $sort = in_array($request->string('sort')->value(), self::SORTABLE, true)
            ? $request->string('sort')->value()
            : 'created_at';

        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        $leads = $master->leads()
            ->with(['event:id,title,slug', 'statusChanges.user:id,name'])
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                    $like = '%'.$request->string('search').'%';

                    $query->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('message', 'like', $like);
                }),
            )
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('eventId'),
                fn (Builder $query) => $query->where('event_id', $request->integer('eventId')),
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $leads->getCollection()->map(MasterLeadData::fromModel(...)),
            'meta' => [
                'page' => $leads->currentPage(),
                'perPage' => $leads->perPage(),
                'total' => $leads->total(),
                'lastPage' => $leads->lastPage(),
            ],
            'statuses' => $this->statuses(),
            'events' => $this->events($master),
        ]);
    }

    /**
     * Мастер двигает заявку по её пути и объясняет почему.
     *
     * Комментарий необязателен, но остаётся в истории: через месяц «почему
     * бронь сняли» не помнит никто, а место в группе к тому времени уже
     * занято кем-то другим.
     */
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $master = $request->user()->master;

        abort_if($master === null || $lead->master_id !== $master->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = LeadStatus::from($validated['status']);
        $previous = $lead->status;

        if ($status !== $previous) {
            $this->guardSeats($lead, $previous, $status);
        }

        DB::transaction(function () use ($lead, $request, $previous, $status, $validated): void {
            $lead->update(['status' => $status]);

            // Пишем и когда статус не изменился: комментарий к заявке —
            // тоже событие её истории, и терять его незачем.
            $lead->statusChanges()->create([
                'user_id' => $request->user()->id,
                'from_status' => $previous,
                'to_status' => $status,
                'comment' => $validated['comment'] ?? null,
            ]);
        });

        $lead->load(['event:id,title,slug', 'statusChanges.user:id,name']);

        return response()->json(MasterLeadData::fromModel($lead));
    }

    /**
     * Бронь занимает место в группе, и мест может не остаться.
     *
     * Проверяем при переводе, а не при показе: между открытием списка
     * и нажатием кнопки последнее место мог занять кто-то другой.
     */
    private function guardSeats(Lead $lead, LeadStatus $previous, LeadStatus $next): void
    {
        if (! $next->holdsSeat() || $previous->holdsSeat() || $lead->event === null) {
            return;
        }

        $left = $lead->event->seatsLeft();

        if ($left !== null && $left < 1) {
            throw ValidationException::withMessages([
                'status' => 'Мест больше нет: в группе '.$lead->event->capacity.'. Освободите место или увеличьте группу в мероприятии.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyList(): array
    {
        return [
            'data' => [],
            'meta' => ['page' => 1, 'perPage' => self::DEFAULT_PER_PAGE, 'total' => 0, 'lastPage' => 1],
            'statuses' => $this->statuses(),
            'events' => [],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return array_map(
            fn (LeadStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            LeadStatus::cases(),
        );
    }

    /**
     * Мероприятия для фильтра — все, включая прошедшие: заявки к ним никуда
     * не делись, и искать их по названию человек будет именно здесь.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function events(Master $master): array
    {
        return $master->events()
            ->orderByDesc('starts_at')
            ->get(['id', 'title'])
            ->map(fn (Event $event): array => ['value' => $event->id, 'label' => $event->title])
            ->all();
    }
}
