<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MemberProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // Номеров может быть несколько, но не бесконечно: список длиннее
            // десятка — это уже не «свои телефоны», а чужая записная книжка.
            'phones' => ['array', 'max:10'],
            'phones.*.phone' => ['required', 'string', 'max:64', 'distinct'],
            'phones.*.isPrimary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'phones.*.phone' => 'телефон',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phones.*.phone.required' => 'Впишите номер или уберите строку.',
            'phones.*.phone.distinct' => 'Этот номер уже есть в списке.',
        ];
    }
}
