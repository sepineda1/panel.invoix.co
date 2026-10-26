<?php

namespace App\Services\Tenants;

use App\Models\Operation;
use App\Models\Tenant;
use App\Enums\OperationStatus;
use App\Enums\OperationType;

class OperationLogger
{
    public function start(?Tenant $tenant, ?int $operatorId, OperationType $type): Operation
    {
        return Operation::query()->create([
            'tenant_id' => $tenant?->id,
            'operator_id' => $operatorId,
            'subject_name' => $tenant?->name ?? '',
            'subject_slug' => $tenant?->slug ?? '',
            'type' => $type,
            'status' => OperationStatus::Running,
        ]);
    }

    public function succeed(Operation $operation, string $output = ''): void
    {
        $operation->forceFill([
            'status' => OperationStatus::Success,
            'output' => $this->trim($output),
        ])->save();
    }

    public function fail(Operation $operation, string $output): void
    {
        $operation->forceFill([
            'status' => OperationStatus::Failed,
            'output' => $this->trim($output),
        ])->save();
    }

    private function trim(string $output): string
    {
        return mb_substr(trim($output), 0, 4000);
    }
}
