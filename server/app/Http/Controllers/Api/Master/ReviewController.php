<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Отзывы о мастере — только чтение.
 *
 * Своё мнение о себе мастер не правит и не удаляет: иначе рейтинг перестаёт
 * что-либо значить. Спорные отзывы снимает площадка, и это её работа.
 * Здесь видны и те, что ещё на проверке, — чтобы человек знал, что о нём
 * написали, до того как это увидят посетители.
 */
class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $master = $request->user()->master;

        if ($master === null) {
            return response()->json(['data' => []]);
        }

        $reviews = $master->reviews()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json([
            'data' => $reviews->map(fn (Review $review): array => [
                'id' => $review->id,
                'author' => $review->user?->name ?? 'Участник',
                'rating' => $review->rating,
                'body' => $review->body,
                'status' => $review->status->value,
                'statusLabel' => $review->status->label(),
                'createdAt' => $review->created_at->toIso8601String(),
            ]),
        ]);
    }
}
