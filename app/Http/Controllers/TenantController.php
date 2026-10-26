<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Http\Requests\DeleteTenantRequest;
use App\Http\Requests\StoreTenantRequest;
use App\Jobs\ActivateTenant;
use App\Jobs\DeactivateTenant;
use App\Jobs\DeleteTenant;
use App\Jobs\ProvisionTenant;
use App\Jobs\RestartTenant;
use App\Models\Tenant;
use App\Support\Secrets;
use App\Support\TenantNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::query()->latest()->get()->map(fn (Tenant $tenant) => $tenant->present());

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenants/Create');
    }

    public function store(StoreTenantRequest $request, Secrets $secrets): RedirectResponse
    {
        $data = $request->validated();
        $names = TenantNames::make();
        $slug = $data['slug'];

        $tenant = Tenant::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => TenantStatus::Provisioning,
            'app_url' => $names->appUrl($slug),
            'database_name' => $names->database($slug),
            'database_user' => $names->user($slug),
            'redis_prefix' => $names->redisPrefix($slug),
            'admin_name' => $data['admin_name'],
            'admin_email' => $data['admin_email'],
            'admin_password' => $secrets->admin(),
        ]);

        ProvisionTenant::dispatch($tenant->id, $request->user()->id);

        return redirect()->route('tenants.show', $tenant);
    }

    public function show(Request $request, Tenant $tenant): Response
    {
        return Inertia::render('Tenants/Show', [
            'tenant' => $tenant->present(),
            'adminPassword' => $this->revealPassword($request, $tenant),
            'operations' => $tenant->operations()->with('operator')->latest()->limit(30)->get()->map->present(),
        ]);
    }

    public function restart(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->guard($tenant, [TenantStatus::Running]);
        RestartTenant::dispatch($tenant->id, $request->user()->id);

        return back()->with('success', 'Reinicio en curso.');
    }

    public function deactivate(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->guard($tenant, [TenantStatus::Running]);
        DeactivateTenant::dispatch($tenant->id, $request->user()->id);

        return back()->with('success', 'La empresa se está desactivando.');
    }

    public function activate(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->guard($tenant, [TenantStatus::Stopped]);
        ActivateTenant::dispatch($tenant->id, $request->user()->id);

        return back()->with('success', 'La empresa se está activando.');
    }

    public function retry(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->guard($tenant, [TenantStatus::Failed]);
        ProvisionTenant::dispatch($tenant->id, $request->user()->id);

        return back()->with('success', 'Reintento en curso.');
    }

    public function destroy(DeleteTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === TenantStatus::Deleting) {
            abort(422);
        }

        DeleteTenant::dispatch($tenant->id, $request->user()->id);

        return back()->with('success', 'Eliminación en curso.');
    }

    public function dismissPassword(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'admin_password' => null,
            'password_shown_at' => null,
        ])->save();

        return back();
    }

    /** @param  list<TenantStatus>  $allowed */
    private function guard(Tenant $tenant, array $allowed): void
    {
        if (! in_array($tenant->status, $allowed, true)) {
            abort(422);
        }
    }

    private function revealPassword(Request $request, Tenant $tenant): ?string
    {
        if (! $tenant->admin_password) {
            return null;
        }

        if ($tenant->password_shown_at === null) {
            $plain = $tenant->admin_password;
            $tenant->forceFill(['password_shown_at' => now()])->save();

            return $plain;
        }

        if ($request->header('X-Inertia-Partial-Data')) {
            return $tenant->admin_password;
        }

        $tenant->forceFill([
            'admin_password' => null,
            'password_shown_at' => null,
        ])->save();

        return null;
    }
}
