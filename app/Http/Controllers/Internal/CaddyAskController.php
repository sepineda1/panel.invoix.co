<?php

namespace App\Http\Controllers\Internal;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CaddyAskController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $domain = strtolower((string) $request->query('domain', ''));
        $suffix = '.'.config('panel.domain');

        if ($domain === '' || ! str_ends_with($domain, $suffix)) {
            return response('no', 404);
        }

        $slug = substr($domain, 0, -strlen($suffix));
        if (! Slug::valid($slug)) {
            return response('no', 404);
        }

        $exists = Tenant::query()
            ->where('slug', $slug)
            ->where('status', '!=', TenantStatus::Deleting->value)
            ->exists();

        return $exists ? response('ok', 200) : response('no', 404);
    }
}
