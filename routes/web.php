<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\Webhook\GithubImageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/webhooks/github/image', GithubImageController::class)->name('webhooks.github');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('tenants.index'));
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/empresas', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/empresas/nueva', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/empresas', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/empresas/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::post('/empresas/{tenant}/reiniciar', [TenantController::class, 'restart'])->name('tenants.restart');
    Route::post('/empresas/{tenant}/desactivar', [TenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::post('/empresas/{tenant}/activar', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('/empresas/{tenant}/reintentar', [TenantController::class, 'retry'])->name('tenants.retry');
    Route::post('/empresas/{tenant}/eliminar', [TenantController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/empresas/{tenant}/contrasena-vista', [TenantController::class, 'dismissPassword'])->name('tenants.dismiss-password');

    Route::get('/actualizacion', [FleetController::class, 'index'])->name('fleet.index');
    Route::post('/actualizacion', [FleetController::class, 'store'])->name('fleet.store');

    Route::get('/bitacora', [OperationController::class, 'index'])->name('operations.index');
});
