<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\TidiesInput;
use Illuminate\Foundation\Http\FormRequest;

class MasterProfileRequest extends FormRequest
{
    use TidiesInput;

    protected function prepareForValidation(): void
    {
        $this->castFlags('isActive', 'removePhoto', 'removeCover');
        $this->normalizeNewlines('bio');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:20000'],
            'city' => ['nullable', 'string', 'max:255'],

            // Витрину можно временно убрать из каталога: это не удаление,
            // мероприятия и отзывы остаются на месте.
            'isActive' => ['boolean'],

            'photo' => ['nullable', 'image', 'max:4096'],
            'removePhoto' => ['boolean'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'removeCover' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'headline' => 'краткое описание',
            'bio' => 'о себе',
            'city' => 'город',
        ];
    }
}
