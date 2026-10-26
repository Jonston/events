<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Lead;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private function master(array $attributes = []): Master
    {
        return Master::create([
            'slug' => 'anna',
            'name' => 'Анна',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function event(Master $master, array $attributes = []): Event
    {
        return Event::create([
            'master_id' => $master->id,
            'slug' => 'practice',
            'title' => 'Практика',
            'starts_at' => now()->addWeek(),
            'price' => 1000,
            'is_published' => true,
            ...$attributes,
        ]);
    }

    public function test_showcase_is_open_to_guests(): void
    {
        $this->getJson('/api/home')->assertOk();
        $this->getJson('/api/masters')->assertOk();
        $this->getJson('/api/events')->assertOk();
    }

    public function test_master_list_skips_inactive(): void
    {
        $this->master(['slug' => 'visible', 'name' => 'Видимая']);
        $this->master(['slug' => 'hidden', 'name' => 'Скрытая', 'is_active' => false]);

        $this->getJson('/api/masters')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Видимая');
    }

    public function test_inactive_master_page_is_not_found(): void
    {
        $this->master(['is_active' => false]);

        $this->getJson('/api/masters/anna')->assertNotFound();
    }

    public function test_event_list_skips_unpublished_and_past(): void
    {
        $master = $this->master();
        $this->event($master, ['slug' => 'now', 'title' => 'Скоро']);
        $this->event($master, ['slug' => 'draft', 'title' => 'Черновик', 'is_published' => false]);
        $this->event($master, ['slug' => 'gone', 'title' => 'Прошло', 'starts_at' => now()->subWeek()]);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Скоро');
    }

    public function test_event_list_carries_the_point_on_the_map(): void
    {
        $master = $this->master();
        $this->event($master, ['slug' => 'here', 'starts_at' => now()->addDay(), 'latitude' => 50.45, 'longitude' => 30.52]);
        $this->event($master, ['slug' => 'nowhere', 'starts_at' => now()->addWeek()]);

        // Без координат в списке карте мероприятий нечего рисовать, а метка
        // без точки — это метка посреди океана.
        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('data.0.lat', 50.45)
            ->assertJsonPath('data.0.lng', 30.52)
            ->assertJsonPath('data.1.lat', null);
    }

    public function test_event_page_splits_price_into_deposit_and_remainder(): void
    {
        $master = $this->master();
        $this->event($master, ['price' => 1000, 'deposit_rate' => 0.3]);

        $response = $this->getJson('/api/events/practice')->assertOk();

        // Предоплата и остаток должны в сумме давать полную цену — иначе
        // человек на странице видит арифметику, которая не сходится.
        $this->assertStringContainsString('300', $response->json('depositFormatted'));
        $this->assertStringContainsString('700', $response->json('remainingFormatted'));
    }

    public function test_lead_stores_contact_and_links_master_through_event(): void
    {
        $master = $this->master();
        $event = $this->event($master);

        $this->postJson('/api/leads', [
            'name' => 'Пётр',
            'phone' => '+380671112233',
            'eventId' => $event->id,
        ])->assertCreated();

        $lead = Lead::sole();
        $this->assertSame('Пётр', $lead->name);
        $this->assertSame($event->id, $lead->event_id);
        // Мастера клиент не присылал — связь восстановлена по мероприятию.
        $this->assertSame($master->id, $lead->master_id);
    }

    public function test_lead_from_a_signed_in_member_keeps_the_account(): void
    {
        $member = User::factory()->create();

        // Токен настоящий, а не подменённый охранник: маршрут открыт гостю,
        // на нём нет middleware, и разбирать заголовок контроллер должен сам.
        // Через `actingAs` эта разница не видна — там подменяется и охранник
        // по умолчанию, при котором ошибка не воспроизводится.
        $token = $member->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/leads', [
            'name' => $member->name,
            'phone' => '+380671112233',
        ])->assertCreated();

        $this->assertSame($member->id, Lead::sole()->user_id);
    }

    public function test_second_lead_for_the_same_event_is_refused(): void
    {
        $event = $this->event($this->master());

        $payload = ['name' => 'Пётр', 'phone' => '+380671112233', 'eventId' => $event->id, 'guestKey' => 'abc123'];

        $this->postJson('/api/leads', $payload)->assertCreated();

        // Тот же браузер, то же мероприятие: вторая заявка ничего не добавляет,
        // а мастеру достаётся два одинаковых контакта.
        $this->postJson('/api/leads', $payload)
            ->assertStatus(409)
            ->assertJsonPath('applied', true);

        $this->assertSame(1, Lead::count());
    }

    public function test_the_same_guest_may_apply_to_another_event(): void
    {
        $master = $this->master();
        $first = $this->event($master);
        $second = $this->event($master, ['slug' => 'vtoroe', 'title' => 'Второе']);

        foreach ([$first, $second] as $event) {
            $this->postJson('/api/leads', [
                'name' => 'Пётр',
                'phone' => '+380671112233',
                'eventId' => $event->id,
                'guestKey' => 'abc123',
            ])->assertCreated();
        }

        $this->assertSame(2, Lead::count());
    }

    public function test_check_tells_the_page_whether_to_show_the_form(): void
    {
        $event = $this->event($this->master());

        $this->getJson("/api/leads/check?eventId={$event->id}&guestKey=abc123")
            ->assertOk()
            ->assertJsonPath('applied', false);

        $this->postJson('/api/leads', [
            'name' => 'Пётр', 'phone' => '+380671112233', 'eventId' => $event->id, 'guestKey' => 'abc123',
        ])->assertCreated();

        $this->getJson("/api/leads/check?eventId={$event->id}&guestKey=abc123")
            ->assertOk()
            ->assertJsonPath('applied', true);
    }

    public function test_a_signed_in_member_is_recognised_without_the_browser_key(): void
    {
        $event = $this->event($this->master());
        $member = User::factory()->create();
        $token = $member->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/leads', [
            'name' => $member->name, 'phone' => '+380671112233', 'eventId' => $event->id, 'guestKey' => 'first',
        ])->assertCreated();

        // Другой браузер, тот же аккаунт — заявка всё равно вторая.
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/leads', [
            'name' => $member->name, 'phone' => '+380509998877', 'eventId' => $event->id, 'guestKey' => 'second',
        ])->assertStatus(409);
    }

    public function test_lead_requires_a_phone(): void
    {
        $this->postJson('/api/leads', ['name' => 'Пётр'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_lead_rejects_filled_honeypot(): void
    {
        $this->postJson('/api/leads', [
            'name' => 'Бот',
            'phone' => '+380671112233',
            'company' => 'ООО Спам',
        ])->assertStatus(422);

        $this->assertSame(0, Lead::count());
    }
}
