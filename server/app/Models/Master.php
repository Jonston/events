<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\Concerns\HasImages;
use Database\Factories\MasterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['user_id', 'slug', 'name', 'headline', 'bio', 'city', 'is_active', 'sort_order'])]
class Master extends Model implements HasMedia
{
    /** @use HasFactory<MasterFactory> */
    use HasFactory;

    use HasImages;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Ключ в адресе — slug: `/masters/anna-lisova` читаемее, чем `/masters/7`. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Master>  $query
     * @return Builder<Master>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Порядок в каталоге задаёт площадка полем `sort_order`; имя — только
     * запасной ключ, чтобы у мастеров с одинаковым весом порядок не прыгал
     * от запроса к запросу.
     *
     * @param  Builder<Master>  $query
     * @return Builder<Master>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Портрет и обложка — по одному файлу на витрину: их место на странице
     * задано вёрсткой, и второй файл в той же роли пришлось бы выбирать.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
        $this->addMediaCollection('cover')->singleFile();
    }

    /**
     * Уменьшенные копии делаются сразу (`nonQueued`): очередь в проекте есть,
     * но воркер под неё не поднят, и отложенная копия просто не появилась бы.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Портрет всегда в круге — квадратная обрезка здесь не портит кадр,
        // а избавляет от разъезжающихся аватаров.
        $this->addMediaConversion('thumb')
            ->performOnCollections('photo')
            ->fit(Fit::Crop, 400, 400)
            ->nonQueued();

        // Обложке задаём только потолок: пропорции решает вёрстка,
        // а снимок с телефона незачем возить целиком.
        $this->addMediaConversion('wide')
            ->performOnCollections('cover')
            ->fit(Fit::Max, 1600, 1600)
            ->nonQueued();
    }

    public function photoUrl(): ?string
    {
        return $this->mediaUrl('photo', 'thumb');
    }

    public function coverUrl(): ?string
    {
        return $this->mediaUrl('cover', 'wide');
    }

    /**
     * Средний балл и число отзывов. Null — оценок ещё нет: показывать
     * «0.0 из 5» у мастера без отзывов значит показывать плохую оценку
     * вместо её отсутствия.
     *
     * @return array{value: float, count: int}|null
     */
    public function ratingSummary(): ?array
    {
        // Значения приходят из withCount/withAvg, если запрос их подгрузил;
        // иначе считаем сами — модель не должна зависеть от формы запроса.
        $count = $this->reviews_count ?? $this->reviews()->where('status', ReviewStatus::Published)->count();

        if ($count === 0) {
            return null;
        }

        $average = $this->reviews_avg_rating
            ?? $this->reviews()->where('status', ReviewStatus::Published)->avg('rating');

        return [
            'value' => round((float) $average, 1),
            'count' => (int) $count,
        ];
    }
}
