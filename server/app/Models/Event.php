<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadStatus;
use App\Models\Concerns\HasImages;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'master_id', 'slug', 'title', 'summary', 'description',
    'starts_at', 'ends_at', 'location', 'latitude', 'longitude',
    'price', 'deposit_rate', 'capacity', 'is_published',
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasImages;

    /** Доля предоплаты, если мероприятие своей не задало. */
    private const DEFAULT_DEPOSIT_RATE = 0.3;

    /** За сколько дней до старта предоплата ещё возвращается. */
    private const REFUND_DAYS_BEFORE = 14;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => 'decimal:2',
            'deposit_rate' => 'float',
            'capacity' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Master, $this> */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('day')->orderBy('starts_at');
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Ближайшие — по началу, а не по концу: многодневная программа, которая
     * уже идёт, в наборе не участвует, и в списке ей не место.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function registerMediaCollections(): void
    {
        // Обложка одна: её место на карточке и в шапке задано вёрсткой.
        $this->addMediaCollection('image')->singleFile();

        // Галерея — сколько угодно снимков, и порядок в ней хранится:
        // это тот же порядок, в котором они лягут на страницу.
        $this->addMediaCollection('gallery');
    }

    /** Почему сразу, а не очередью — см. Master::registerMediaConversions(). */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Одна копия на карточку, шапку страницы и просмотр во весь экран:
        // все трое показывают снимок целиком, разница в ширине контейнера.
        $this->addMediaConversion('card')
            ->fit(Fit::Max, 1600, 1600)
            ->nonQueued();

        // Плитка галереи квадратная: в сетке из десятка кадров разнобой
        // пропорций читается как сбой вёрстки, а не как замысел.
        $this->addMediaConversion('thumb')
            ->performOnCollections('gallery')
            ->fit(Fit::Crop, 400, 400)
            ->nonQueued();
    }

    public function imageUrl(): ?string
    {
        return $this->mediaUrl('image', 'card');
    }

    /**
     * Сколько мест занято.
     *
     * Считается по броням, а не хранится счётчиком: счётчик расходится
     * с заявками при первом же двойном переводе статуса, а восстановить
     * его потом неоткуда.
     */
    public function seatsTaken(): int
    {
        return (int) ($this->leads_booked_count
            ?? $this->leads()->whereIn('status', LeadStatus::seatHolding())->count());
    }

    /** Сколько осталось. Null — мест не ограничивали. */
    public function seatsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->seatsTaken());
    }

    public function depositAmount(): float
    {
        return round((float) $this->price * ($this->deposit_rate ?? self::DEFAULT_DEPOSIT_RATE), 2);
    }

    public function remainingAmount(): float
    {
        return round((float) $this->price - $this->depositAmount(), 2);
    }

    /** Последний день, когда предоплата возвращается полностью. */
    public function refundDeadline(): Carbon
    {
        return $this->starts_at->copy()->subDays(self::REFUND_DAYS_BEFORE);
    }
}
