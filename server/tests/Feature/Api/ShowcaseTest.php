<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Lead;
use App\Models\Master;
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
