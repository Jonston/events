<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Ai\Agents\CabinetAssistant;
use App\Ai\CabinetMap;
use App\Ai\Tools\NavigateCabinet;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Tools\Request as ToolRequest;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Помощник кабинета: диалог, память и границы того, куда он может отвести. */
class CabinetAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    private function user(UserRole $role = UserRole::Member): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user->refresh();
    }

    /** Собирает ответ из кусочков потока — так его увидит чат на клиенте. */
    private function streamedText(string $stream): string
    {
        return collect(explode("\n", $stream))
            ->filter(fn (string $line): bool => str_starts_with($line, 'data: {'))
            ->map(fn (string $line): array => (array) json_decode(substr($line, 6), true))
            ->where('type', 'text-delta')
            ->pluck('delta')
            ->join('');
    }

    public function test_assistant_is_closed_to_guests(): void
    {
        $this->postJson('/api/cabinet/assistant', ['message' => 'Привет'])->assertUnauthorized();
        $this->getJson('/api/cabinet/assistant')->assertUnauthorized();
    }

    public function test_message_is_required(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/cabinet/assistant', ['message' => ' '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_answer_is_streamed_and_remembered(): void
    {
        Ai::fakeAgent(CabinetAssistant::class, ['Записи лежат в разделе «Мои записи».']);

        $user = $this->user();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/cabinet/assistant', ['message' => 'Где мои записи?']);

        $response->assertOk();
        $this->assertSame('Записи лежат в разделе «Мои записи».', $this->streamedText($response->streamedContent()));

        Ai::assertAgentWasPrompted(CabinetAssistant::class, 'Где мои записи?');

        $this->assertDatabaseHas('agent_conversations', [
            'participant_type' => $user->getMorphClass(),
            'participant_id' => $user->id,
        ]);
    }

    public function test_history_returns_the_conversation_and_reset_clears_it(): void
    {
        Ai::fakeAgent(CabinetAssistant::class, ['Заглянем в профиль.']);

        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->post('/api/cabinet/assistant', ['message' => 'Где менять телефон?'])
            ->assertOk()
            ->streamedContent();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/cabinet/assistant')
            ->assertOk()
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.0.parts.0.text', 'Где менять телефон?');

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/cabinet/assistant')
            ->assertOk();

        $this->assertSame(0, Conversation::query()->count());
        $this->assertDatabaseCount('agent_conversation_messages', 0);
    }

    public function test_second_question_continues_the_same_conversation(): void
    {
        Ai::fakeAgent(CabinetAssistant::class, ['Первый ответ.', 'Второй ответ.']);

        $user = $this->user();

        foreach (['Первый вопрос', 'Второй вопрос'] as $message) {
            $this->actingAs($user, 'sanctum')
                ->post('/api/cabinet/assistant', ['message' => $message])
                ->assertOk()
                ->streamedContent();
        }

        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_navigation_is_limited_to_sections_open_to_the_role(): void
    {
        $member = $this->user();
        $master = $this->user(UserRole::Master);

        $this->assertNotContains('master_events', CabinetMap::keysFor($member));
        $this->assertContains('master_events', CabinetMap::keysFor($master));

        $tool = new NavigateCabinet($member);

        $this->assertStringContainsString(
            '/cabinet/profile',
            (string) $tool->handle(new ToolRequest(['section' => 'cabinet_profile']))
        );

        $this->assertStringNotContainsString(
            '/master/events',
            (string) $tool->handle(new ToolRequest(['section' => 'master_events']))
        );
    }
}
