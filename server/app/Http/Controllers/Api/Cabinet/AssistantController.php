<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet;

use App\Ai\Agents\CabinetAssistant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Responses\StreamableAgentResponse;

/**
 * Помощник кабинета.
 *
 * Диалог у человека один и живёт в базе: панель открывают с телефона,
 * закрывают, возвращаются через день — и разговор продолжается с того же
 * места, а не начинается заново. Начать сначала можно кнопкой.
 */
class AssistantController extends Controller
{
    /** Сколько последних сообщений показываем при открытии панели. */
    private const HISTORY_LIMIT = 50;

    /**
     * История диалога в том виде, в каком её ждёт чат на клиенте.
     */
    public function history(Request $request): JsonResponse
    {
        $conversation = $this->lastConversation($request->user());

        if ($conversation === null) {
            return response()->json(['messages' => []]);
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            // Вопрос и ответ на него ложатся в одну секунду, и без разбора
            // по идентификатору порядок в паре выпадает случайным: ULID растёт
            // монотонно, поэтому он и решает спор.
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->filter(fn (ConversationMessage $message): bool => filled($message->content))
            ->map(fn (ConversationMessage $message): array => [
                'id' => (string) $message->id,
                'role' => $message->role,
                'parts' => [['type' => 'text', 'text' => $message->content]],
            ])
            ->values();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Ответ агента потоком.
     *
     * Протокол — тот же, что у клиентского AI SDK: панель собрана
     * на его `useChat`, и своего формата здесь заводить не за чем.
     */
    public function prompt(Request $request): StreamableAgentResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Без ключа модели запрос всё равно уйдёт в никуда, но упадёт уже
        // внутри провайдера — трассировкой на пол-экрана. Скажем прямо.
        abort_if(
            blank(config('ai.providers.'.config('ai.default').'.key')),
            503,
            'Помощник не настроен: в окружении нет ключа модели.',
        );

        $user = $request->user();

        return (new CabinetAssistant($user))
            ->continueLastConversation($user)
            ->stream($validated['message'])
            ->usingVercelDataProtocol();
    }

    /**
     * Забыть разговор и начать с чистого листа.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $conversations = $user->conversations()->pluck('id');

            ConversationMessage::whereIn('conversation_id', $conversations)->delete();
            Conversation::whereIn('id', $conversations)->delete();
        });

        return response()->json(['messages' => []]);
    }

    private function lastConversation(User $user): ?Conversation
    {
        return $user->conversations()->latest('updated_at')->first();
    }
}
