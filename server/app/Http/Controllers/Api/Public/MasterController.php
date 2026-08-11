<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Data\MasterCardData;
use App\Data\MasterDetailData;
use App\Http\Controllers\Controller;
use App\Models\Master;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;

class MasterController extends Controller
{
    public function index(): JsonResponse
    {
        $masters = Master::query()
            ->active()
            ->ordered()
            ->withCount(['events' => fn (Builder $query) => $query->published()->upcoming()])
            ->withCount(['reviews' => fn (Builder $query) => $query->published()])
            ->withAvg(['reviews' => fn (Builder $query) => $query->published()], 'rating')
            ->get();

        return response()->json([
            'data' => $masters->map(MasterCardData::fromModel(...)),
        ]);
    }

    public function show(Master $master): MasterDetailData
    {
        abort_unless($master->is_active, 404);

        // У `load` замыкание получает связь, а не построитель запроса —
        // отсюда другой тип, чем у `withCount` ниже.
        $master->load([
            'events' => fn (Relation $relation) => $relation->published()->upcoming()->with('master'),
            'reviews' => fn (Relation $relation) => $relation->published()->with('user')->latest(),
        ]);
        $master->loadCount(['reviews' => fn (Builder $query) => $query->published()]);
        $master->loadAvg(['reviews' => fn (Builder $query) => $query->published()], 'rating');

        return MasterDetailData::fromModel($master);
    }
}
