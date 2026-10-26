<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\CabinetMap;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Переход по кабинету.
 *
 * Сам переход делает браузер: инструмент лишь называет раздел, а панель
 * помощника видит результат вызова и уводит страницу. Сервер решает, куда
 * можно, — список разделов приходит из карты кабинета и уже отфильтрован
 * ролью, поэтому подсказать дорогу в чужой раздел модель не сможет,
 * даже если её об этом попросят.
 */
final class NavigateCabinet implements Tool
{
    public function __construct(private readonly User $user) {}

    public function name(): string
    {
        return 'navigate_cabinet';
    }

    public function description(): Stringable|string
    {
        return 'Открывает раздел кабинета на экране собеседника. Вызывай, когда человек просит куда-то перейти, '
            .'или когда его вопрос решается на конкретной странице: сначала переход, потом короткое пояснение, что он там увидит.';
    }

    public function handle(Request $request): Stringable|string
    {
        $section = CabinetMap::find($this->user, (string) $request['section']);

        if ($section === null) {
            return 'Такого раздела нет или он закрыт для этого человека. Не предлагай переход, объясни словами.';
        }

        return (string) json_encode([
            'path' => $section['path'],
            'label' => $section['label'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()
                ->enum(CabinetMap::keysFor($this->user))
                ->description('Ключ раздела кабинета из списка в инструкции.')
                ->required(),
        ];
    }
}
