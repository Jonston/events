<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MemberProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Свои данные участника: имя и телефоны.
 *
 * Почта здесь не меняется — она же логин, и смена адреса требует
 * подтверждения нового: это отдельный разговор, а не поле в форме.
 */
class ProfileController extends Controller
{
    public function show(Request $request): UserData
    {
        return UserData::fromModel($request->user()->load(['roles', 'phones']));
    }

    public function update(MemberProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $user->update(['name' => $data['name']]);
            $this->syncPhones($user, $data['phones'] ?? []);
        });

        return response()->json(UserData::fromModel($user->load(['roles', 'phones'])));
    }

    /**
     * Список приходит целиком: что осталось — то и есть телефоны человека.
     *
     * Основной ровно один. Если форма не отметила ни одного — им становится
     * первый: заявке нужен номер по умолчанию, и выбирать его молча
     * лучше, чем не подставить ничего.
     *
     * @param  array<int, array{phone: string, isPrimary?: bool}>  $phones
     */
    private function syncPhones(User $user, array $phones): void
    {
        $user->phones()->delete();

        $primaryAt = collect($phones)->search(fn (array $phone): bool => ($phone['isPrimary'] ?? false) === true);
        $primaryAt = $primaryAt === false ? 0 : $primaryAt;

        foreach (array_values($phones) as $position => $phone) {
            $user->phones()->create([
                'phone' => trim($phone['phone']),
                'is_primary' => $position === $primaryAt,
            ]);
        }

        $user->unsetRelation('phones');
    }
}
