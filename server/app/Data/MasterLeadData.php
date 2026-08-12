<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Lead;
use Spatie\LaravelData\Data;

/**
 * Заявка в кабинете мастера.
 *
 * Контакты отдаются целиком: заявка существует ровно затем, чтобы по ней
 * перезвонили, и прятать телефон от того, кому его оставили, бессмысленно.
 */
class MasterLeadData extends Data
{
    /**
     * @param  array<int, LeadStatusChangeData>  $history
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $message,
        public string $status,
        public string $statusLabel,
        public string $statusColor,
        public ?int $eventId,
        public ?string $eventTitle,
        public ?string $eventSlug,
        /** Заявка от вошедшего участника, а не от гостя. */
        public bool $fromMember,
        public string $createdAt,
        public array $history,
    ) {}

    public static function fromModel(Lead $lead): self
    {
        return new self(
            id: $lead->id,
            name: $lead->name,
            phone: $lead->phone,
            email: $lead->email,
            message: $lead->message,
            status: $lead->status->value,
            statusLabel: $lead->status->label(),
            statusColor: $lead->status->color(),
            eventId: $lead->event_id,
            eventTitle: $lead->event?->title,
            eventSlug: $lead->event?->slug,
            fromMember: $lead->user_id !== null,
            createdAt: $lead->created_at->toIso8601String(),
            history: $lead->statusChanges->map(LeadStatusChangeData::fromModel(...))->all(),
        );
    }
}
