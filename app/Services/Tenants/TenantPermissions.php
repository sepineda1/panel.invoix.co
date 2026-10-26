<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use App\Support\Slug;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class TenantPermissions
{
    public function __construct(private readonly DockerClient $docker) {}

    public function grant(Tenant $tenant): void
    {
        if (! Slug::valid($tenant->slug)) {
            throw new RuntimeException('El subdominio no es válido.');
        }

        $container = $tenant->slug.'-app';
        $paths = ['/var/www/html/storage', '/var/www/html/bootstrap/cache'];

        foreach ([
            ['exec', $container, 'chown', '-R', 'www-data:www-data', ...$paths],
            ['exec', $container, 'chmod', '-R', 'ug+rwx', ...$paths],
        ] as $arguments) {
            $result = $this->docker->run($arguments);
            if (! $result->ok) {
                throw new RuntimeException('No se pudieron corregir los permisos de '.$tenant->slug.': '.$result->output);
            }
        }
    }
}
