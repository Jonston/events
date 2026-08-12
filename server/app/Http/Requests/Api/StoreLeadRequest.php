<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\TidiesInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    use TidiesInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeNewlines('message');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Обязателен именно телефон: по нему перезванивают. Email нужен
            // не всем и остаётся необязательным.
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'eventId' => ['nullable', 'integer', 'exists:events,id'],
            // Метка браузера гостя: по ней не принимаем вторую заявку
            // на то же мероприятие.
            'guestKey' => ['nullable', 'string', 'max:64'],
            'masterId' => ['nullable', 'integer', 'exists:masters,id'],
            // Ловушка для ботов: человек этого поля не видит и не заполняет,
            // поэтому заполненное — признак автоматической отправки.
            'company' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Представьтесь, пожалуйста.',
            'phone.required' => 'Оставьте телефон — по нему мы и перезвоним.',
            'email.email' => 'Проверьте адрес: похоже, в нём опечатка.',
            'company.prohibited' => 'Заявка не отправлена.',
        ];
    }
}
