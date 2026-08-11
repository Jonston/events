<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Вход по токену Sanctum: клиент — отдельное Nuxt-приложение на другом
 * origin, поэтому сессионных cookie у него нет и авторизация держится
 * на personal access token.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password')->value(), (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->deviceName())->plainTextToken,
            'user' => UserData::fromModel($user->load('roles')),
        ]);
    }

    /**
     * Выход гасит только текущий токен: другие устройства человека
     * остаются в системе.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли из системы.']);
    }

    public function me(Request $request): UserData
    {
        return UserData::fromModel($request->user()->load('roles'));
    }
}
