<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Cabinet\ProfileController as CabinetProfileController;
use App\Http\Controllers\Api\Master\EventController as MasterEventController;
use App\Http\Controllers\Api\Master\LeadController as MasterLeadController;
use App\Http\Controllers\Api\Master\OverviewController as MasterOverviewController;
use App\Http\Controllers\Api\Master\ProfileController as MasterProfileController;
use App\Http\Controllers\Api\Master\ReviewController as MasterReviewController;
use App\Http\Controllers\Api\Public\EventController;
use App\Http\Controllers\Api\Public\HomeController;
use App\Http\Controllers\Api\Public\LeadController;
use App\Http\Controllers\Api\Public\MasterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Витрина
|--------------------------------------------------------------------------
|
| Открыты всем: каталог мастеров и мероприятий — это то, за чем человек
| приходит на площадку, и требовать вход, чтобы посмотреть цены, значит
| терять его до того, как он что-то увидел.
|
*/

Route::get('/home', HomeController::class)->name('api.home');

Route::get('/masters', [MasterController::class, 'index'])->name('api.masters.index');
Route::get('/masters/{master}', [MasterController::class, 'show'])->name('api.masters.show');

Route::get('/events', [EventController::class, 'index'])->name('api.events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('api.events.show');

// Ограничение по адресу, а не по сессии: заявку оставляет гость,
// и сессии, к которой можно было бы привязать счётчик, у него нет.
Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.leads.store');

/*
|--------------------------------------------------------------------------
| Аккаунт и кабинеты
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('api.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // `/user` — конечная точка, которую по умолчанию опрашивает
    // nuxt-auth-sanctum на клиенте; `/me` оставлен как явный синоним.
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    /*
     * Кабинет участника открыт любому вошедшему: мастер и администратор —
     * тоже участники площадки, и запирать от них собственные данные не за что.
     */
    Route::prefix('cabinet')
        ->name('api.cabinet.')
        ->group(function (): void {
            Route::get('/profile', [CabinetProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [CabinetProfileController::class, 'update'])->name('profile.update');
        });

    /*
     * Кабинет мастера. Роль — вход в раздел, а право на конкретное
     * мероприятие проверяет политика: у мастера может не быть витрины,
     * и тогда править ему нечего.
     */
    Route::prefix('master')
        ->middleware('role:'.UserRole::Master->value.'|'.UserRole::Admin->value)
        ->name('api.master.')
        ->group(function (): void {
            Route::get('/overview', MasterOverviewController::class)->name('overview');

            Route::get('/profile', [MasterProfileController::class, 'show'])->name('profile.show');
            Route::post('/profile', [MasterProfileController::class, 'update'])->name('profile.update');

            Route::get('/events', [MasterEventController::class, 'index'])->name('events.index');
            Route::post('/events', [MasterEventController::class, 'store'])->name('events.store');
            Route::get('/events/{event:id}', [MasterEventController::class, 'show'])->name('events.show');
            // POST, а не PUT: форма несёт файл обложки, а multipart в PUT
            // браузер не отправляет — пришлось бы городить _method.
            Route::post('/events/{event:id}', [MasterEventController::class, 'update'])->name('events.update');
            Route::delete('/events/{event:id}', [MasterEventController::class, 'destroy'])->name('events.destroy');

            Route::get('/leads', [MasterLeadController::class, 'index'])->name('leads.index');
            Route::patch('/leads/{lead}/status', [MasterLeadController::class, 'updateStatus'])->name('leads.status');

            Route::get('/reviews', [MasterReviewController::class, 'index'])->name('reviews.index');
        });

    Route::prefix('admin')
        ->middleware('role:'.UserRole::Admin->value)
        ->name('api.admin.')
        ->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        });
});
