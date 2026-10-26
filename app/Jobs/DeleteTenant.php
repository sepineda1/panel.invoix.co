<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenants\TenantActions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class DeleteTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $tenantId, public ?int $operatorId) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('tenant-'.$this->tenantId))->expireAfter(900)];
    }

    public function handle(TenantActions $actions): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if ($tenant) {
            $actions->delete($tenant, $this->operatorId);
        }
    }
}
