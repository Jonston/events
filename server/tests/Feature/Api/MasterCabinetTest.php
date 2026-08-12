<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterCabinetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    /** Мастер вместе со своей витриной. */
    private function master(string $slug = 'anna'): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Master->value);

        Master::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'name' => 'Мастер '.$slug,
            'is_active' => true,
        ]);

        return $user->refresh();
    }

    /**
     * Подменённый публичный диск — вместе с его настройкой `url`.
     *
     * `Storage::fake()` заводит диск с одним только корнем, и адрес файла
     * выходит относительным. Именно на этом картинки и не показывались:
     * клиент живёт на другом домене и «/storage/…» просит у себя.
     */
    private function fakePublicDisk(): void
    {
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
    }

    private function assertAbsoluteUrl(mixed $url): void
    {
        $this->assertIsString($url);
        $this->assertStringStartsWith('http', $url);
    }

    /** Мероприятие этого мастера с заданным размером группы. */
    private function eventOf(User $master, ?int $capacity = null): Event
    {
        return Event::create([
            'master_id' => $master->master->id,
            'slug' => 'praktika-'.uniqid(),
            'title' => 'Практика',
            'starts_at' => now()->addMonth(),
            'price' => 1000,
            'capacity' => $capacity,
            'is_published' => true,
        ]);
    }

    private function leadFor(Event $event, string $name = 'Пётр'): Lead
    {
        return Lead::create([
            'master_id' => $event->master_id,
            'event_id' => $event->id,
            'name' => $name,
            'phone' => '+380671112233',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Практика выходного дня',
            'startsAt' => now()->addMonth()->format('Y-m-d\TH:i'),
            'price' => 1500,
            'isPublished' => true,
            ...$overrides,
        ];
    }

    public function test_cabinet_is_closed_to_members(): void
    {
        $member = User::factory()->create();
        $member->assignRole(UserRole::Member->value);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/master/events')
            ->assertForbidden();
    }

    public function test_master_creates_event_with_schedule(): void
    {
        $master = $this->master();

        $response = $this->actingAs($master, 'sanctum')->postJson('/api/master/events', $this->payload([
            'sessions' => [
                ['day' => now()->addMonth()->toDateString(), 'startsAt' => '10:00', 'endsAt' => '12:00', 'title' => 'Первая часть'],
                ['day' => now()->addMonth()->toDateString(), 'startsAt' => '13:00', 'title' => 'Вторая часть'],
            ],
        ]))->assertCreated();

        $this->assertSame('Практика выходного дня', $response->json('title'));
        $this->assertCount(2, $response->json('sessions'));

        $event = Event::sole();
        $this->assertSame($master->master->id, $event->master_id);
        // Адрес страницы собирается из названия, а не остаётся пустым.
        $this->assertNotEmpty($event->slug);
    }

    public function test_created_event_appears_on_the_showcase(): void
    {
        $master = $this->master();

        $this->actingAs($master, 'sanctum')
            ->postJson('/api/master/events', $this->payload())
            ->assertCreated();

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Практика выходного дня');
    }

    public function test_draft_stays_out_of_the_showcase_but_shows_in_cabinet(): void
    {
        $master = $this->master();

        $this->actingAs($master, 'sanctum')
            ->postJson('/api/master/events', $this->payload(['isPublished' => false]))
            ->assertCreated();

        $this->getJson('/api/events')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($master, 'sanctum')
            ->getJson('/api/master/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isPublished', false);
    }

    public function test_editing_replaces_the_whole_schedule(): void
    {
        $master = $this->master();
        $day = now()->addMonth()->toDateString();

        $id = $this->actingAs($master, 'sanctum')->postJson('/api/master/events', $this->payload([
            'sessions' => [
                ['day' => $day, 'startsAt' => '10:00', 'title' => 'Было'],
                ['day' => $day, 'startsAt' => '11:00', 'title' => 'И это было'],
            ],
        ]))->json('id');

        $this->actingAs($master, 'sanctum')->postJson("/api/master/events/{$id}", $this->payload([
            'sessions' => [
                ['day' => $day, 'startsAt' => '09:00', 'title' => 'Стало'],
            ],
        ]))->assertOk()->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.title', 'Стало');
    }

    public function test_master_cannot_touch_another_masters_event(): void
    {
        $owner = $this->master('owner');
        $stranger = $this->master('stranger');

        $id = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/master/events', $this->payload())
            ->json('id');

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/master/events/{$id}", $this->payload(['title' => 'Взлом']))
            ->assertForbidden();

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/master/events/{$id}")
            ->assertForbidden();
    }

    public function test_admin_editing_does_not_steal_the_event(): void
    {
        $owner = $this->master('owner');

        $adminUser = User::factory()->create();
        $adminUser->assignRole(UserRole::Admin->value);
        Master::create(['user_id' => $adminUser->id, 'slug' => 'admin-shop', 'name' => 'Витрина админа']);

        $id = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/master/events', $this->payload())
            ->json('id');

        $this->actingAs($adminUser->refresh(), 'sanctum')
            ->postJson("/api/master/events/{$id}", $this->payload(['title' => 'Поправил опечатку']))
            ->assertOk();

        // Мероприятие осталось у своего мастера, а не переехало к тому,
        // кто его правил.
        $this->assertSame($owner->master->id, Event::find($id)->master_id);
    }

    public function test_deleting_removes_the_event(): void
    {
        $master = $this->master();

        $id = $this->actingAs($master, 'sanctum')
            ->postJson('/api/master/events', $this->payload())
            ->json('id');

        $this->actingAs($master, 'sanctum')
            ->deleteJson("/api/master/events/{$id}")
            ->assertOk();

        $this->assertSame(0, Event::count());
    }

    public function test_end_before_start_is_rejected(): void
    {
        $master = $this->master();

        $this->actingAs($master, 'sanctum')->postJson('/api/master/events', $this->payload([
            'startsAt' => now()->addMonth()->format('Y-m-d\TH:i'),
            'endsAt' => now()->addWeek()->format('Y-m-d\TH:i'),
        ]))->assertStatus(422)->assertJsonValidationErrors('endsAt');
    }

    public function test_profile_is_created_on_first_visit(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Master->value);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/master/profile')
            ->assertOk();

        // Новая витрина скрыта: в каталоге ей пока нечего показать.
        $this->assertFalse($response->json('isActive'));
        $this->assertSame(1, Master::count());
    }

    public function test_profile_update_stores_photo(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        $response = $this->actingAs($master, 'sanctum')->post('/api/master/profile', [
            'name' => 'Анна Лисова',
            'headline' => 'Телесные практики',
            'isActive' => true,
            'photo' => UploadedFile::fake()->image('portrait.jpg', 900, 900),
        ])->assertOk()->assertJsonPath('name', 'Анна Лисова');

        $media = $master->master->refresh()->getFirstMedia('photo');
        $this->assertNotNull($media);
        Storage::disk('public')->assertExists($media->id.'/portrait.jpg');

        $this->assertAbsoluteUrl($response->json('photo'));
    }

    public function test_gallery_keeps_order_and_drops_what_was_removed(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        $created = $this->actingAs($master, 'sanctum')->post('/api/master/events', $this->payload([
            'gallery' => [
                ['file' => UploadedFile::fake()->image('one.jpg', 800, 600)],
                ['file' => UploadedFile::fake()->image('two.jpg', 800, 600)],
                ['file' => UploadedFile::fake()->image('three.jpg', 800, 600)],
            ],
        ]))->assertCreated();

        $ids = array_column($created->json('gallery'), 'id');
        $this->assertCount(3, $ids);
        $this->assertAbsoluteUrl($created->json('gallery.0.thumb'));

        $event = Event::firstOrFail();

        // Второй снимок выброшен, оставшиеся идут задом наперёд, и к ним
        // добавлен новый: форма присылает галерею целиком, а не список правок.
        $updated = $this->actingAs($master, 'sanctum')->post("/api/master/events/{$event->id}", $this->payload([
            'gallery' => [
                ['id' => $ids[2]],
                ['file' => UploadedFile::fake()->image('four.jpg', 800, 600)],
                ['id' => $ids[0]],
            ],
        ]))->assertOk();

        $names = $event->refresh()->getMedia('gallery')->pluck('file_name')->all();
        $this->assertSame(['three.jpg', 'four.jpg', 'one.jpg'], $names);

        // Снимок ушёл не только из списка, но и с диска.
        Storage::disk('public')->assertMissing($ids[1].'/two.jpg');
    }

    public function test_gallery_arrives_when_the_form_sends_files_only(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        // Так галерею присылает браузер: у нового снимка нет ничего, кроме
        // файла, и в обычных полях запроса его не видно совсем.
        $this->actingAs($master, 'sanctum')->call(
            'POST',
            '/api/master/events',
            $this->payload(),
            [],
            ['gallery' => [['file' => UploadedFile::fake()->image('one.jpg', 800, 600)]]],
            ['HTTP_ACCEPT' => 'application/json'],
        )->assertCreated();

        $this->assertCount(1, Event::firstOrFail()->getMedia('gallery'));
    }

    public function test_gallery_item_without_id_and_file_is_rejected(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        $this->actingAs($master, 'sanctum')->post('/api/master/events', $this->payload([
            'gallery' => [['caption' => 'без файла']],
        ]))->assertStatus(422)->assertJsonValidationErrors('gallery.0.file');
    }

    public function test_multiline_text_is_stored_without_carriage_returns(): void
    {
        $master = $this->master();

        $this->actingAs($master, 'sanctum')->post('/api/master/profile', [
            'name' => 'Анна Лисова',
            'bio' => "Первый абзац.\r\n\r\nВторой абзац.",
        ])->assertOk();

        // CR — след multipart-формы. Доехав до базы, он разводит разметку
        // сервера и клиента: браузер возвращает те же строки уже без него.
        $this->assertSame("Первый абзац.\n\nВторой абзац.", $master->master->refresh()->bio);
    }

    public function test_new_photo_replaces_the_previous_one(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        foreach (['first.jpg', 'second.jpg'] as $name) {
            $this->actingAs($master, 'sanctum')->post('/api/master/profile', [
                'name' => 'Анна Лисова',
                'photo' => UploadedFile::fake()->image($name, 900, 900),
            ])->assertOk();
        }

        // Витрина показывает один портрет: вторая загрузка заменяет первую,
        // а не копится рядом с ней.
        $photos = $master->master->refresh()->getMedia('photo');
        $this->assertCount(1, $photos);
        $this->assertSame('second.jpg', $photos->first()->file_name);
    }

    public function test_photo_is_removed_on_request(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        $this->actingAs($master, 'sanctum')->post('/api/master/profile', [
            'name' => 'Анна Лисова',
            'photo' => UploadedFile::fake()->image('portrait.jpg', 900, 900),
        ])->assertOk();

        $this->actingAs($master, 'sanctum')->post('/api/master/profile', [
            'name' => 'Анна Лисова',
            'removePhoto' => '1',
        ])->assertOk()->assertJsonPath('photo', null);

        $this->assertCount(0, $master->master->refresh()->getMedia('photo'));
    }

    public function test_event_cover_is_stored_and_returned(): void
    {
        $this->fakePublicDisk();
        $master = $this->master();

        $response = $this->actingAs($master, 'sanctum')->post('/api/master/events', $this->payload([
            'image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
        ]))->assertCreated();

        $this->assertNotNull(Event::firstOrFail()->getFirstMedia('image'));
        $this->assertAbsoluteUrl($response->json('image'));
    }

    public function test_master_sees_own_leads_and_moves_their_status(): void
    {
        $master = $this->master();
        $other = $this->master('other');

        $lead = Lead::create(['master_id' => $master->master->id, 'name' => 'Пётр', 'phone' => '+380671112233']);
        Lead::create(['master_id' => $other->master->id, 'name' => 'Чужой', 'phone' => '+380670000000']);

        $this->actingAs($master, 'sanctum')
            ->getJson('/api/master/leads')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Пётр');

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$lead->id}/status", ['status' => LeadStatus::Booked->value])
            ->assertOk()
            ->assertJsonPath('status', 'booked');
    }

    public function test_status_change_is_written_to_history_with_a_comment(): void
    {
        $master = $this->master();
        $lead = Lead::create(['master_id' => $master->master->id, 'name' => 'Пётр', 'phone' => '+380671112233']);

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$lead->id}/status", [
                'status' => LeadStatus::Spam->value,
                'comment' => 'Реклама услуг, не участник.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'spam')
            ->assertJsonPath('history.0.fromStatus', 'new')
            ->assertJsonPath('history.0.toStatus', 'spam')
            ->assertJsonPath('history.0.comment', 'Реклама услуг, не участник.')
            ->assertJsonPath('history.0.author', $master->name);
    }

    public function test_booking_takes_a_seat_and_refund_returns_it(): void
    {
        $master = $this->master();
        $event = $this->eventOf($master, capacity: 2);
        $lead = $this->leadFor($event);

        $this->assertSame(2, $event->seatsLeft());

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$lead->id}/status", ['status' => LeadStatus::Booked->value])
            ->assertOk();

        $this->assertSame(1, $event->refresh()->seatsLeft());

        // Возврат освобождает место: тем же счётом, без отдельного счётчика.
        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$lead->id}/status", ['status' => LeadStatus::Refunded->value])
            ->assertOk();

        $this->assertSame(2, $event->refresh()->seatsLeft());
    }

    public function test_booking_is_refused_when_no_seats_left(): void
    {
        $master = $this->master();
        $event = $this->eventOf($master, capacity: 1);
        $first = $this->leadFor($event);
        $second = $this->leadFor($event, 'Второй');

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$first->id}/status", ['status' => LeadStatus::Booked->value])
            ->assertOk();

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$second->id}/status", ['status' => LeadStatus::Booked->value])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(LeadStatus::New, $second->refresh()->status);
    }

    public function test_leads_are_filtered_by_status_and_event(): void
    {
        $master = $this->master();
        $event = $this->eventOf($master, capacity: 5);

        $this->leadFor($event, 'С мероприятия');
        Lead::create(['master_id' => $master->master->id, 'name' => 'Со страницы', 'status' => LeadStatus::Spam->value]);

        $this->actingAs($master, 'sanctum')
            ->getJson('/api/master/leads?status=spam')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Со страницы');

        $this->actingAs($master, 'sanctum')
            ->getJson("/api/master/leads?eventId={$event->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'С мероприятия');

        $this->actingAs($master, 'sanctum')
            ->getJson('/api/master/leads?search=страницы')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_master_cannot_touch_another_masters_lead(): void
    {
        $master = $this->master();
        $other = $this->master('other');

        $lead = Lead::create(['master_id' => $other->master->id, 'name' => 'Чужой', 'phone' => '+380670000000']);

        $this->actingAs($master, 'sanctum')
            ->patchJson("/api/master/leads/{$lead->id}/status", ['status' => LeadStatus::Booked->value])
            ->assertForbidden();
    }
}
