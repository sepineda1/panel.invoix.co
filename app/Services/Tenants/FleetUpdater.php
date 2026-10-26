<?php

namespace App\Services\Tenants;

use App\Enums\OperationType;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use App\Support\ImageRef;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FleetUpdater
{
    public function __construct(
        private readonly DockerClient $docker,
        private readonly HealthProbe $health,
        private readonly TenantFiles $files,
        private readonly CaddySites $caddy,
        private readonly OperationLogger $logger,
        private readonly TenantPermissions $permissions,
    ) {}

    public function run(string $image, ?int $operatorId): void
    {
        if (! ImageRef::valid($image)) {
            throw new \InvalidArgumentException('La referencia de la imagen no es válida.');
        }

        $tenants = Tenant::query()
            ->whereIn('status', [TenantStatus::Running->value, TenantStatus::Failed->value])
            ->orderBy('id')
            ->get();

        $failures = [];
        $this->progress($image, $tenants->count(), 0, null, $failures, true);

        foreach ($tenants as $index => $tenant) {
            $this->progress($image, $tenants->count(), $index, $tenant->slug, $failures, true);

            try {
                $this->one($tenant, $image, $operatorId);
            } catch (Throwable $e) {
                $failures[] = ['slug' => $tenant->slug, 'error' => $e->getMessage()];
            }
        }

        $this->progress($image, $tenants->count(), $tenants->count(), null, $failures, false);
    }

    private function one(Tenant $tenant, string $image, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Update);

        try {
            $tenant->forceFill([
                'status' => TenantStatus::Updating,
                'last_error' => null,
                'image_ref' => $image,
            ])->save();

            $this->files->writeCompose($tenant);
            $file = $this->files->composePath($tenant);
            $project = 'invoix-'.$tenant->slug;

            $pull = $this->docker->compose($file, ['pull'], $project);
            if (! $pull->ok) {
                throw new \RuntimeException('docker compose pull falló: '.$pull->output);
            }

            $up = $this->docker->compose($file, ['up', '-d', '--remove-orphans'], $project);
            if (! $up->ok) {
                throw new \RuntimeException('docker compose up falló: '.$up->output);
            }

            $this->health->waitUntilUp($tenant->slug, (int) config('panel.health_timeout'));
            $this->permissions->grant($tenant);

            $tenant->forceFill([
                'status' => TenantStatus::Running,
                'image_updated_at' => now(),
                'last_error' => null,
            ])->save();
            $this->caddy->sync();
            $this->logger->succeed($operation, trim($pull->output."\n".$up->output));
        } catch (Throwable $e) {
            $tenant->forceFill([
                'status' => TenantStatus::Failed,
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ])->save();
            $this->logger->fail($operation, $e->getMessage());
            throw $e;
        }
    }

    /** @param  list<array{slug: string, error: string}>  $failures */
    private function progress(string $image, int $total, int $processed, ?string $current, array $failures, bool $active): void
    {
        Cache::put('fleet.update', [
            'active' => $active,
            'image' => $image,
            'total' => $total,
            'processed' => $processed,
            'current' => $current,
            'failures' => $failures,
            'finished_at' => $active ? null : now()->toIso8601String(),
        ], now()->addDay());
    }
}
