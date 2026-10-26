<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Support\ImageRef;
use RuntimeException;

class TenantFiles
{
    public function root(Tenant $tenant): string
    {
        return rtrim((string) config('panel.data_root'), '/\\').DIRECTORY_SEPARATOR.$tenant->slug;
    }

    public function envPath(Tenant $tenant): string
    {
        return $this->root($tenant).DIRECTORY_SEPARATOR.'.env';
    }

    public function composePath(Tenant $tenant): string
    {
        return $this->root($tenant).DIRECTORY_SEPARATOR.'docker-compose.yml';
    }

    public function storagePath(Tenant $tenant): string
    {
        return $this->root($tenant).DIRECTORY_SEPARATOR.'storage';
    }

    public function writeEnv(Tenant $tenant): void
    {
        $this->ensureRoot($tenant);

        $lines = [
            '# Generado por el panel INVOIX. No editar a mano.',
            $this->line('APP_NAME', $tenant->name),
            'APP_ENV=production',
            'APP_DEBUG=false',
            $this->line('APP_KEY', (string) $tenant->app_key),
            $this->line('APP_URL', $tenant->app_url),
            'APP_TIMEZONE=America/Bogota',
            'APP_LOCALE=es',
            'APP_FALLBACK_LOCALE=es',
            'LOG_CHANNEL=stderr',
            'LOG_LEVEL=info',
            'DB_CONNECTION=mysql',
            $this->line('DB_HOST', (string) config('panel.tenant_db_host')),
            $this->line('DB_PORT', (string) config('panel.tenant_db_port')),
            $this->line('DB_DATABASE', $tenant->database_name),
            $this->line('DB_USERNAME', $tenant->database_user),
            $this->line('DB_PASSWORD', (string) $tenant->database_password),
            'SESSION_DRIVER=database',
            'SESSION_LIFETIME=120',
            'BROADCAST_CONNECTION=log',
            'FILESYSTEM_DISK=local',
            'QUEUE_CONNECTION=redis',
            'CACHE_STORE=redis',
            'REDIS_CLIENT=phpredis',
            $this->line('REDIS_HOST', (string) config('panel.tenant_redis_host')),
            'REDIS_PORT=6379',
            'REDIS_DB=0',
            'REDIS_CACHE_DB=0',
            $this->line('REDIS_PREFIX', $tenant->redis_prefix),
            $this->line('ERP_COMPANY_NAME', $tenant->name),
            $this->line('ERP_ADMIN_NAME', $tenant->admin_name),
            $this->line('ERP_ADMIN_EMAIL', $tenant->admin_email),
        ];

        if ($tenant->admin_password) {
            $lines[] = $this->line('ERP_ADMIN_PASSWORD', (string) $tenant->admin_password);
        }

        $this->write($this->envPath($tenant), implode("\n", $lines)."\n", 0600);
    }

    public function writeStorage(Tenant $tenant): void
    {
        foreach ([
            'app/public',
            'app/private',
            'framework/cache/data',
            'framework/sessions',
            'framework/views',
            'logs',
        ] as $dir) {
            $path = $this->storagePath($tenant).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
                throw new RuntimeException('No se pudo crear el almacenamiento.');
            }
        }
    }

    public function writeCompose(Tenant $tenant): void
    {
        $image = (string) $tenant->image_ref;
        if (! ImageRef::valid($image)) {
            throw new RuntimeException('La referencia de la imagen no es válida.');
        }

        $slug = $tenant->slug;
        $network = (string) config('panel.network');
        $env = $this->envPath($tenant);
        $lines = [
            'name: invoix-'.$slug,
            '',
            'services:',
        ];

        $services = [
            'app' => [
                'command' => '["php-fpm"]',
                'networks' => ['internal', $network],
                'depends' => [],
                'environment' => [],
            ],
            'web' => [
                'command' => '["nginx", "-g", "daemon off;"]',
                'networks' => ['internal', $network],
                'depends' => ['app'],
                'environment' => ['FPM_HOST' => $slug.'-app'],
            ],
            'horizon' => [
                'command' => '["php", "artisan", "horizon"]',
                'networks' => [$network],
                'depends' => ['app'],
                'environment' => [],
            ],
            'scheduler' => [
                'command' => '["php", "artisan", "schedule:work"]',
                'networks' => [$network],
                'depends' => ['app'],
                'environment' => [],
            ],
        ];

        foreach ($services as $name => $service) {
            $lines[] = '  '.$name.':';
            $lines[] = '    image: '.$image;
            $lines[] = '    container_name: '.$slug.'-'.$name;
            $lines[] = '    restart: unless-stopped';
            $lines[] = '    env_file:';
            $lines[] = '      - '.$env;
            foreach ($service['environment'] as $key => $value) {
                $lines[] = '    environment:';
                $lines[] = '      '.$key.': '.$value;
            }
            $lines[] = '    command: '.$service['command'];
            $lines[] = '    networks: ['.implode(', ', $service['networks']).']';
            if ($service['depends'] !== []) {
                $lines[] = '    depends_on: ['.implode(', ', $service['depends']).']';
            }
            array_push($lines, ...$this->volumeLines($slug));
        }

        $lines[] = '';
        $lines[] = 'networks:';
        $lines[] = '  internal:';
        $lines[] = '    name: invoix-'.$slug.'-net';
        $lines[] = '  '.$network.':';
        $lines[] = '    external: true';
        $lines[] = '    name: '.$network;

        $volume = config('panel.tenant_volume');
        if (is_string($volume) && $volume !== '') {
            $lines[] = '';
            $lines[] = 'volumes:';
            $lines[] = '  '.$volume.':';
            $lines[] = '    external: true';
            $lines[] = '    name: '.$volume;
        }

        $this->write($this->composePath($tenant), implode("\n", $lines)."\n");
    }

    public function deleteTree(Tenant $tenant): void
    {
        $root = $this->root($tenant);
        if (is_dir($root)) {
            $this->removeDirectory($root);
        }
    }

    /** @return list<string> */
    private function volumeLines(string $slug): array
    {
        $volume = config('panel.tenant_volume');
        if (is_string($volume) && $volume !== '') {
            return [
                '    volumes:',
                '      - type: volume',
                '        source: '.$volume,
                '        target: /var/www/html/storage',
                '        volume:',
                '          subpath: '.$slug.'/storage',
            ];
        }

        $source = rtrim((string) config('panel.host_root'), '/\\').'/'.$slug.'/storage';

        return [
            '    volumes:',
            '      - '.$source.':/var/www/html/storage',
        ];
    }

    private function ensureRoot(Tenant $tenant): void
    {
        $root = $this->root($tenant);
        if (! is_dir($root) && ! mkdir($root, 0770, true) && ! is_dir($root)) {
            throw new RuntimeException('No se pudo crear la carpeta de la empresa.');
        }
    }

    private function write(string $path, string $contents, int $mode = 0644): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0770, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta de la empresa.');
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('No se pudo escribir un archivo de la empresa.');
        }

        chmod($path, $mode);
    }

    private function line(string $key, string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_.:\/@+\-=]*$/', $value)) {
            return $key.'='.$value;
        }

        return $key.'="'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    private function removeDirectory(string $dir): void
    {
        $base = rtrim((string) config('panel.data_root'), '/\\');
        $prefix = $base.DIRECTORY_SEPARATOR;
        if (! str_starts_with($dir, $prefix)) {
            throw new RuntimeException('Ruta fuera del directorio de empresas.');
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
