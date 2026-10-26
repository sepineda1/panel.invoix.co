<?php

namespace App\Services\Tenants;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use RuntimeException;

class CaddySites
{
    public function __construct(private readonly DockerClient $docker) {}

    public function sync(): void
    {
        $blocks = ['# Generado por el panel INVOIX. No editar a mano.'];

        Tenant::query()
            ->where('status', '!=', TenantStatus::Deleting->value)
            ->orderBy('slug')
            ->each(function (Tenant $tenant) use (&$blocks) {
                $blocks[] = $this->site($tenant);
            });

        $path = (string) config('panel.caddy_path');
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar la configuración de Caddy.');
        }

        if (file_put_contents($path, implode("\n\n", $blocks)."\n") === false) {
            throw new RuntimeException('No se pudo escribir la configuración de Caddy.');
        }

        $result = $this->docker->run([
            'docker', 'exec', (string) config('panel.caddy_container'),
            'caddy', 'reload', '--config', '/etc/caddy/Caddyfile', '--adapter', 'caddyfile',
        ]);

        if (! $result->ok) {
            throw new RuntimeException('Caddy no recargó: '.$result->output);
        }
    }

    private function site(Tenant $tenant): string
    {
        $host = $tenant->slug.'.'.config('panel.domain');
        $body = $tenant->status === TenantStatus::Stopped
            ? '    respond 404'
            : '    reverse_proxy '.$tenant->slug.'-web:80';
        $tls = (string) config('panel.caddy_tls', 'on_demand');

        if ($tls === 'off') {
            return "http://{$host} {\n{$body}\n}";
        }

        if ($tls === 'internal') {
            return "{$host} {\n    tls internal\n{$body}\n}";
        }

        return "{$host} {\n    tls {\n        on_demand\n    }\n{$body}\n}";
    }
}
