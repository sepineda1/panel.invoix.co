<?php

namespace App\Services\Tenants;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use RuntimeException;

class TenantMailSync
{
    public function __construct(
        private readonly TenantFiles $files,
        private readonly DockerClient $docker,
    ) {}

    public function run(): int
    {
        $updated = 0;

        Tenant::query()
            ->where('status', '!=', TenantStatus::Deleting->value)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use (&$updated) {
                if ($this->sync($tenant)) {
                    $updated++;
                }
            });

        return $updated;
    }

    private function sync(Tenant $tenant): bool
    {
        $path = $this->files->envPath($tenant);
        if (! is_file($path)) {
            return false;
        }

        $before = file_get_contents($path);
        $this->files->writeEnv($tenant);
        if ($before === file_get_contents($path)) {
            return false;
        }

        if ($tenant->status !== TenantStatus::Running) {
            return true;
        }

        $compose = $this->files->composePath($tenant);
        if (! is_file($compose)) {
            return true;
        }

        $result = $this->docker->compose($compose, ['up', '-d', '--force-recreate'], 'invoix-'.$tenant->slug);
        if (! $result->ok) {
            throw new RuntimeException('No se pudo reiniciar '.$tenant->slug.' para aplicar el correo: '.$result->output);
        }

        return true;
    }
}
