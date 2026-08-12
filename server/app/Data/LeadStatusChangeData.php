<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\LeadStatusChange;
use Spatie\LaravelData\Data;

class LeadStatusChangeData extends Data
{
    public function __construct(
        public int $id,
        public ?string $fromStatus,
        public ?string $fromLabel,
        public string $toStatus,
        public string $toLabel,
        public ?string $comment,
        /** Кто перевёл. Null — запись появилась без участия человека. */
        public ?string $author,
        public string $createdAt,
    ) {}

    public static function fromModel(LeadStatusChange $change): self
    {
        return new self(
            id: $change->id,
            fromStatus: $change->from_status?->value,
            fromLabel: $change->from_status?->label(),
            toStatus: $change->to_status->value,
            toLabel: $change->to_status->label(),
            comment: $change->comment,
            author: $change->user?->name,
            createdAt: $change->created_at->toIso8601String(),
        );
    }
}
