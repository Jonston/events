<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Заявки, пришедшие к этому мастеру.
 *
 * Контакты отдаются целиком: заявка существует ровно затем, чтобы по ней
 * перезвонили, и прятать телефон от того, кому его оставили, бессмысленно.
 */
class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $master = $request->user()->master;

        if ($master === null) {
            return response()->json(['data' => []]);
        }

        $leads = $master->leads()
            ->with('event:id,title,slug')
            ->latest()
            ->get();

        return response()->json([
            'data' => $leads->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'message' => $lead->message,
                'status' => $lead->status->value,
                'statusLabel' => $lead->status->label(),
                'eventTitle' => $lead->event?->title,
                'eventSlug' => $lead->event?->slug,
                'createdAt' => $lead->created_at->toIso8601String(),
            ]),
            'statuses' => array_map(
                fn (LeadStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                LeadStatus::cases(),
            ),
        ]);
    }

    /**
     * Мастер двигает заявку по своему пути: позвонил, договорились,
     * состоялось. Больше в заявке ему менять нечего — контакты писал не он.
     */
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $master = $request->user()->master;

        abort_if($master === null || $lead->master_id !== $master->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
        ]);

        $lead->update(['status' => $validated['status']]);

        return response()->json([
            'status' => $lead->status->value,
            'statusLabel' => $lead->status->label(),
        ]);
    }
}
