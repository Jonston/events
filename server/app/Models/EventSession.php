<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'day', 'starts_at', 'ends_at', 'title', 'note'])]
class EventSession extends Model
{
    /** @use HasFactory<EventSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day' => 'date',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Время в расписании показывается без секунд: «10:30», а не «10:30:00». */
    public function timeRange(): string
    {
        $start = substr((string) $this->starts_at, 0, 5);

        if ($this->ends_at === null) {
            return $start;
        }

        return $start.'–'.substr((string) $this->ends_at, 0, 5);
    }
}
