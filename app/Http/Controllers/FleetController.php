<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Jobs\UpdateFleet;
use App\Models\Tenant;
use App\Services\Tenants\PublishedImage;
use App\Support\ImageRef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class FleetController extends Controller
{
    public function index(PublishedImage $images): Response
    {
        $image = $images->current();

        return Inertia::render('Fleet/Index', [
            'image' => $image,
            'imageValid' => ImageRef::valid($image),
            'using' => Tenant::query()->where('image_ref', $image)->where('status', TenantStatus::Running->value)->count(),
            'eligible' => Tenant::query()->whereIn('status', [TenantStatus::Running->value, TenantStatus::Failed->value])->count(),
            'progress' => Cache::get('fleet.update'),
            'tenants' => Tenant::query()->orderBy('name')->get()->map->present(),
        ]);
    }

    public function store(Request $request, PublishedImage $images): RedirectResponse
    {
        $progress = Cache::get('fleet.update');
        if (is_array($progress) && ($progress['active'] ?? false)) {
            return back()->with('error', 'Ya hay una actualización en curso.');
        }

        $image = $images->current();
        if (! ImageRef::valid($image)) {
            return back()->with('error', 'No hay una imagen publicada válida.');
        }

        UpdateFleet::dispatch($image, $request->user()->id);

        return back()->with('success', 'Actualización en curso.');
    }
}
