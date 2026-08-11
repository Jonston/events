<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Data\MasterProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MasterProfileRequest;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Витрина мастера из его кабинета.
 *
 * Профиль заводится здесь же, а не отдельным шагом: у человека с ролью
 * мастера страницы может ещё не быть, и первый заход в кабинет — самый
 * подходящий момент её создать.
 */
class ProfileController extends Controller
{
    public function show(Request $request): MasterProfileData
    {
        return MasterProfileData::fromModel($this->profile($request));
    }

    /** Как и у мероприятия, POST здесь ради файлов, а не создание записи. */
    public function update(MasterProfileRequest $request): JsonResponse
    {
        $master = $this->profile($request);
        $data = $request->validated();

        $master->fill([
            'name' => $data['name'],
            'headline' => $data['headline'] ?? null,
            'bio' => $data['bio'] ?? null,
            'city' => $data['city'] ?? null,
            'is_active' => (bool) ($data['isActive'] ?? $master->is_active),
        ]);

        $master->save();

        // Картинки после сохранения: media library привязывает файл к id,
        // а у только что заведённой витрины его до этого нет.
        $master->syncImage('photo', $request->file('photo'), ($data['removePhoto'] ?? false) === true);
        $master->syncImage('cover', $request->file('cover'), ($data['removeCover'] ?? false) === true);

        return response()->json(MasterProfileData::fromModel($master));
    }

    /**
     * Витрина текущего мастера; если её нет — заводим на месте.
     *
     * Пустая страница лучше отсутствующей: человек видит форму и понимает,
     * что делать, вместо сообщения об ошибке, из которого это не следует.
     */
    private function profile(Request $request): Master
    {
        $user = $request->user();

        return $user->master ?? Master::create([
            'user_id' => $user->id,
            'slug' => $this->uniqueSlug($user->name),
            'name' => $user->name,
            // Новая витрина скрыта: показывать в каталоге страницу без
            // описания и мероприятий — плохая услуга и человеку, и каталогу.
            'is_active' => false,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, language: 'ru') ?: 'master';
        $slug = $base;
        $suffix = 2;

        while (Master::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
