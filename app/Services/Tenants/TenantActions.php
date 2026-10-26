<?php

namespace App\Services\Tenants;

use App\Enums\OperationType;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use Throwable;

class TenantActions
{
    public function __construct(
        private readonly DockerClient $docker,
        private readonly HealthProbe $health,
        private readonly TenantFiles $files,
        private readonly CaddySites $caddy,
        private readonly DatabaseProvisioner $databases,
        private readonly OperationLogger $logger,
        private readonly TenantPermissions $permissions,
    ) {}

    public function restart(Tenant $tenant, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Restart);

        try {
            $this->compose($tenant, ['restart']);
            $this->health->waitUntilUp($tenant->slug, (int) config('panel.health_timeout'));
            $this->permissions->grant($tenant);
            $tenant->forceFill(['status' => TenantStatus::Running, 'last_error' => null])->save();
            $this->logger->succeed($operation, 'Contenedores reiniciados.');
        } catch (Throwable $e) {
            $this->fail($tenant, $operation, $e);
        }
    }

    public function deactivate(Tenant $tenant, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Deactivate);

        try {
            $this->compose($tenant, ['stop']);
            $tenant->forceFill(['status' => TenantStatus::Stopped, 'last_error' => null])->save();
            $this->caddy->sync();
            $this->logger->succeed($operation, 'Contenedores detenidos.');
        } catch (Throwable $e) {
            $this->fail($tenant, $operation, $e);
        }
    }

    public function activate(Tenant $tenant, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Activate);

        try {
            $this->compose($tenant, ['up', '-d', '--remove-orphans']);
            $this->health->waitUntilUp($tenant->slug, (int) config('panel.health_timeout'));
            $this->permissions->grant($tenant);
            $tenant->forceFill(['status' => TenantStatus::Running, 'last_error' => null])->save();
            $this->caddy->sync();
            $this->logger->succeed($operation, 'Contenedores en línea.');
        } catch (Throwable $e) {
            $this->fail($tenant, $operation, $e);
        }
    }

    public function delete(Tenant $tenant, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Delete);

        try {
            $tenant->forceFill(['status' => TenantStatus::Deleting, 'last_error' => null])->save();
            $this->caddy->sync();

            $file = $this->files->composePath($tenant);
            if (is_file($file)) {
                $result = $this->docker->compose($file, ['down', '--remove-orphans'], 'invoix-'.$tenant->slug);
                if (! $result->ok) {
                    throw new \RuntimeException('docker compose down falló: '.$result->output);
                }
            }

            $this->files->deleteTree($tenant);
            $this->databases->drop($tenant->database_name, $tenant->database_user);
            $this->logger->succeed($operation, 'Empresa eliminada.');
            $tenant->delete();
            $this->caddy->sync();
        } catch (Throwable $e) {
            if ($tenant->exists) {
                $this->fail($tenant, $operation, $e);
            }
        }
    }

    /** @param  list<string>  $arguments */
    private function compose(Tenant $tenant, array $arguments): void
    {
        $file = $this->files->composePath($tenant);
        if (! is_file($file)) {
            throw new \RuntimeException('No existe el compose de la empresa.');
        }

        $result = $this->docker->compose($file, $arguments, 'invoix-'.$tenant->slug);
        if (! $result->ok) {
            throw new \RuntimeException('docker compose '.implode(' ', $arguments).' falló: '.$result->output);
        }
    }

    private function fail(Tenant $tenant, \App\Models\Operation $operation, Throwable $e): void
    {
        $tenant->forceFill([
            'status' => TenantStatus::Failed,
            'last_error' => mb_substr($e->getMessage(), 0, 500),
        ])->save();
        $this->logger->fail($operation, $e->getMessage());
    }
}
