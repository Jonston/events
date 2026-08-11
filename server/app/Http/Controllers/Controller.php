<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Право на конкретную запись контроллеры проверяют через `authorize()`:
     * роль открывает раздел, а владельца мероприятия знает только политика.
     */
    use AuthorizesRequests;
}
