<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Список пользователей площадки для админки.
 */
class UserController extends Controller
{
    /** Сколько строк отдаём, если клиент не попросил иначе. */
    private const DEFAULT_PER_PAGE = 15;

    /** Потолок на размер страницы: без него `?perPage=100000` выгружает всю базу. */
    private const MAX_PER_PAGE = 100;

    /** Поля, по которым разрешена сортировка. Список закрытый — иначе `?sort=password`. */
    private const SORTABLE = ['id', 'name', 'email', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->integer('perPage', self::DEFAULT_PER_PAGE), 1),
            self::MAX_PER_PAGE,
        );

        $sort = in_array($request->string('sort')->value(), self::SORTABLE, true)
            ? $request->string('sort')->value()
            : 'created_at';

        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->with('roles')
            ->search($request->string('search')->value())
            ->when(
                $request->filled('role'),
                fn ($query) => $query->whereHas(
                    'roles',
                    fn ($roles) => $roles->where('name', $request->string('role')),
                ),
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $users->getCollection()->map(
                fn (User $user): UserData => UserData::fromModel($user),
            ),
            'meta' => [
                'page' => $users->currentPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'lastPage' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user): UserData
    {
        return UserData::fromModel($user->load('roles'));
    }
}
