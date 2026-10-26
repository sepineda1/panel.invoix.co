<?php

namespace App\Services\Tenants;

use App\Enums\OperationType;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use App\Support\ProvisioningPlan;
use App\Support\Secrets;
use Throwable;

class Provisioner
{
    public function __construct(
        private readonly DockerClient $docker,
        private readonly DatabaseProvisioner $databases,
        private readonly HealthProbe $health,
        private readonly TenantFiles $files,
        private readonly CaddySites $caddy,
        private readonly Secrets $secrets,
        private readonly PublishedImage $images,
        private readonly OperationLogger $logger,
    ) {}

    public function run(Tenant $tenant, ?int $operatorId): void
    {
        $operation = $this->logger->start($tenant, $operatorId, OperationType::Create);
        $tenant->forceFill([
            'status' => TenantStatus::Provisioning,
            'last_error' => null,
        ])->save();

        $log = [];

        try {
            foreach (ProvisioningPlan::remaining($tenant->checkpoint) as $step) {
                $log[] = $this->step($tenant, $step);
                $tenant->forceFill(['checkpoint' => $step])->save();
            }

            $this->logger->succeed($operation, implode("\n", $log));
        } catch (Throwable $e) {
            $tenant->forceFill([
                'status' => TenantStatus::Failed,
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ])->save();
            $log[] = $e->getMessage();
            $this->logger->fail($operation, implode("\n", $log));
        }
    }

    private function step(Tenant $tenant, string $step): string
    {
        return match ($step) {
            'database' => $this->database($tenant),
            'env' => $this->env($tenant),
            'storage' => $this->storage($tenant),
            'compose' => $this->compose($tenant),
            'up' => $this->up($tenant),
            'health' => $this->health($tenant),
            'finish' => $this->finish($tenant),
            default => throw new \RuntimeException('Paso de aprovisionamiento desconocido.'),
        };
    }

    private function database(Tenant $tenant): string
    {
        if (! $tenant->database_password) {
            $tenant->database_password = $this->secrets->database();
            $tenant->save();
        }

        $this->databases->ensure(
            $tenant->database_name,
            $tenant->database_user,
            (string) $tenant->database_password,
        );

        return 'Base '.$tenant->database_name.' lista.';
    }

    private function env(Tenant $tenant): string
    {
        if (! $tenant->app_key) {
            $tenant->app_key = $this->secrets->appKey();
            $tenant->save();
        }

        $this->files->writeEnv($tenant);

        return 'Archivo .env escrito.';
    }

    private function storage(Tenant $tenant): string
    {
        $this->files->writeStorage($tenant);

        return 'Almacenamiento creado.';
    }

    private function compose(Tenant $tenant): string
    {
        if (! $tenant->image_ref) {
            $tenant->image_ref = $this->images->current();
            $tenant->save();
        }

        $this->files->writeCompose($tenant);

        return 'Compose escrito con '.$tenant->image_ref.'.';
    }

    private function up(Tenant $tenant): string
    {
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

        $this->caddy->sync();

        return trim($pull->output."\n".$up->output);
    }

    private function health(Tenant $tenant): string
    {
        $this->health->waitUntilUp($tenant->slug, (int) config('panel.health_timeout'));

        return $tenant->app_url.'/up respondió 200.';
    }

    private function finish(Tenant $tenant): string
    {
        $tenant->forceFill([
            'status' => TenantStatus::Running,
            'last_error' => null,
            'image_updated_at' => now(),
        ])->save();
        $this->caddy->sync();

        return 'Empresa en línea.';
    }
}
