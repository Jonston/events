<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'deviceName' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Имя токена в списке устройств. Клиент может прислать своё
     * («Админка», «Кабинет мастера»), иначе берём User-Agent.
     */
    public function deviceName(): string
    {
        return $this->string('deviceName')->value()
            ?: (string) ($this->userAgent() ?? 'unknown');
    }
}
