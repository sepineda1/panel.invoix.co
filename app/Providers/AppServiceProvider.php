<?php

namespace App\Providers;

use App\Services\Docker\DockerClient;
use App\Services\Docker\FakeDockerClient;
use App\Services\Docker\ProcessDockerClient;
use App\Services\Tenants\CurlHealthProbe;
use App\Services\Tenants\DatabaseProvisioner;
use App\Services\Tenants\FakeDatabaseProvisioner;
use App\Services\Tenants\FakeHealthProbe;
use App\Services\Tenants\HealthProbe;
use App\Services\Tenants\MysqlDatabaseProvisioner;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('testing')) {
            $this->app->singleton(DockerClient::class, FakeDockerClient::class);
            $this->app->singleton(DatabaseProvisioner::class, FakeDatabaseProvisioner::class);
            $this->app->singleton(HealthProbe::class, FakeHealthProbe::class);

            return;
        }

        $this->app->singleton(DockerClient::class, ProcessDockerClient::class);
        $this->app->singleton(DatabaseProvisioner::class, MysqlDatabaseProvisioner::class);
        $this->app->singleton(HealthProbe::class, CurlHealthProbe::class);
    }

    public function boot(): void
    {
        if (is_string(config('app.url')) && config('app.url') !== '') {
            URL::forceRootUrl(config('app.url'));
        }

        Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null) => '/'.ltrim($path, '/'));

        Horizon::auth(fn ($request) => $request->user() !== null);
    }
}
