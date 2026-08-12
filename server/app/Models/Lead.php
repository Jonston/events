<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'master_id', 'event_id', 'user_id', 'guest_key', 'name', 'phone', 'email', 'message',
    'source', 'status', 'client_ip', 'client_user_agent', 'landing_url',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'deposit_paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Master, $this> */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * История статусов, свежая сверху: в кабинете сначала смотрят,
     * что было последним, а не с чего всё началось.
     *
     * @return HasMany<LeadStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(LeadStatusChange::class)->latest('id');
    }
}
