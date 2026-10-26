<?php

namespace App\Models;

use App\Enums\OperationStatus;
use App\Enums\OperationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'operator_id', 'subject_name', 'subject_slug', 'type', 'status', 'output',
])]
class Operation extends Model
{
    protected function casts(): array
    {
        return [
            'type' => OperationType::class,
            'status' => OperationStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /** @return array<string, mixed> */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'output' => $this->output,
            'company' => $this->subject_name,
            'slug' => $this->subject_slug,
            'operator' => $this->operator?->name ?? 'Sistema',
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ];
    }
}
