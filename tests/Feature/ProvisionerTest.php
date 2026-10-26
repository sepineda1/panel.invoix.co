<?php

use App\Models\Tenant;
use App\Services\Docker\DockerClient;
use App\Services\Docker\FakeDockerClient;
use App\Services\Tenants\DatabaseProvisioner;
use App\Services\Tenants\FakeDatabaseProvisioner;
use App\Services\Tenants\FakeHealthProbe;
use App\Services\Tenants\HealthProbe;
use App\Services\Tenants\Provisioner;
use App\Services\Tenants\TenantFiles;
use App\Services\Tenants\TenantMailSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $root = sys_get_temp_dir().'/invoix-panel-'.uniqid();
    mkdir($root.'/caddy', 0777, true);

    config([
        'panel.mail' => [
            'host' => 'smtp.invoix.co',
            'port' => '587',
            'username' => 'support@invoix.co',
            'password' => 'clave secreta',
            'from_address' => 'support@invoix.co',
            'scheme' => '',
        ],
        'panel.data_root' => $root.'/tenants',
        'panel.host_root' => $root.'/tenants',
        'panel.tenant_volume' => 'invoix_tenant_data',
        'panel.domain' => 'invoix.co',
        'panel.caddy_path' => $root.'/caddy/tenants.caddy',
        'panel.caddy_tls' => 'off',
        'panel.image' => 'ghcr.io/sepineda1/invoix:abc1234',
        'panel.network' => 'invoix',
        'panel.tenant_db_host' => 'mysql',
        'panel.tenant_redis_host' => 'redis',
    ]);

    $this->docker = new FakeDockerClient;
    $this->databases = new FakeDatabaseProvisioner;
    $this->health = new FakeHealthProbe;
    $this->app->instance(DockerClient::class, $this->docker);
    $this->app->instance(DatabaseProvisioner::class, $this->databases);
    $this->app->instance(HealthProbe::class, $this->health);
});

function empresa(array $extra = []): Tenant
{
    return Tenant::query()->create(array_merge([
        'name' => 'Gogo',
        'slug' => 'gogo',
        'status' => 'provisioning',
        'app_url' => 'https://gogo.invoix.co',
        'database_name' => 'invoix_gogo',
        'database_user' => 'invoix_gogo',
        'redis_prefix' => 'gogo-',
        'admin_name' => 'Ana Ruiz',
        'admin_email' => 'ana@gogo.test',
        'admin_password' => 'Abcdefgh123456',
    ], $extra));
}

it('aprovisiona con el doble de docker y separa redis por prefijo', function () {
    $tenant = empresa();

    app(Provisioner::class)->run($tenant, null);

    $tenant->refresh();
    expect($tenant->status->value)->toBe('running')
        ->and($tenant->image_ref)->toBe('ghcr.io/sepineda1/invoix:abc1234')
        ->and($this->databases->ensured[0]['database'])->toBe('invoix_gogo')
        ->and($this->databases->ensured[0]['user'])->toBe('invoix_gogo')
        ->and(strlen($this->databases->ensured[0]['password']))->toBe(32)
        ->and($this->health->calls)->toBe(['gogo']);

    $env = file_get_contents(app(TenantFiles::class)->envPath($tenant));
    expect($env)->toContain('REDIS_PREFIX=gogo-')
        ->and($env)->toContain('REDIS_DB=0')
        ->and($env)->toContain('REDIS_CACHE_DB=0')
        ->and($env)->toContain('APP_URL=https://gogo.invoix.co')
        ->and($env)->toContain('QUEUE_CONNECTION=redis')
        ->and($env)->toContain('ERP_ADMIN_EMAIL=ana@gogo.test')
        ->and($env)->toContain('MAIL_MAILER=smtp')
        ->and($env)->toContain('MAIL_HOST=smtp.invoix.co')
        ->and($env)->toContain('MAIL_FROM_ADDRESS=support@invoix.co')
        ->and($env)->toContain('MAIL_FROM_NAME=Gogo')
        ->and($env)->toContain('MAIL_PASSWORD="clave secreta"')
        ->and($env)->not->toContain('DIAN');

    $compose = file_get_contents(app(TenantFiles::class)->composePath($tenant));
    expect($compose)->toContain('container_name: gogo-web')
        ->and($compose)->toContain('container_name: gogo-app')
        ->and($compose)->toContain('container_name: gogo-horizon')
        ->and($compose)->toContain('container_name: gogo-scheduler')
        ->and($compose)->toContain('subpath: gogo/storage');

    $calls = array_values(array_filter($this->docker->calls, fn (array $call) => $call['file'] !== null));
    expect($calls[0]['arguments'])->toBe(['pull'])
        ->and($calls[1]['arguments'])->toBe(['up', '-d', '--remove-orphans'])
        ->and($calls[0]['command'][0])->toBe('docker')
        ->and($calls[0]['command'])->not->toContain('gogo;rm');
});

it('reanuda sin crear otra base si el almacenamiento ya quedó listo', function () {
    $tenant = empresa([
        'status' => 'failed',
        'checkpoint' => 'storage',
        'database_password' => 'abcdefghijklmnopqrstuvwxyz012345',
        'app_key' => 'base64:'.base64_encode(random_bytes(32)),
        'image_ref' => 'ghcr.io/sepineda1/invoix:abc1234',
        'last_error' => 'se cortó',
    ]);

    app(Provisioner::class)->run($tenant, null);

    expect($this->databases->ensured)->toBe([])
        ->and($tenant->fresh()->status->value)->toBe('running')
        ->and($tenant->fresh()->last_error)->toBeNull()
        ->and($this->health->calls)->toBe(['gogo']);
});

it('copia el correo del panel y reinicia la empresa que ya está en línea', function () {
    config(['panel.mail.host' => '']);
    $tenant = empresa([
        'status' => 'running',
        'database_password' => 'abcdefghijklmnopqrstuvwxyz012345',
        'app_key' => 'base64:'.base64_encode(random_bytes(32)),
        'image_ref' => 'ghcr.io/sepineda1/invoix:abc1234',
    ]);
    app(TenantFiles::class)->writeEnv($tenant);
    app(TenantFiles::class)->writeCompose($tenant);

    config(['panel.mail.host' => 'smtp.invoix.co']);

    expect(app(TenantMailSync::class)->run())->toBe(1)
        ->and(file_get_contents(app(TenantFiles::class)->envPath($tenant)))->toContain('MAIL_HOST=smtp.invoix.co')
        ->and($this->docker->calls[0]['arguments'])->toBe(['up', '-d', '--force-recreate']);
});
