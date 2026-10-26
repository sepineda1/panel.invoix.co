<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateFleet;
use App\Services\Tenants\PublishedImage;
use App\Support\ImageRef;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GithubImageController extends Controller
{
    public function __invoke(Request $request, PublishedImage $images): JsonResponse
    {
        $secret = (string) config('panel.webhook_secret');
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if ($secret === '' || ! hash_equals($expected, $signature)) {
            abort(401);
        }

        $image = (string) $request->input('image', '');
        if (! ImageRef::valid($image)) {
            abort(422, 'Imagen no válida.');
        }

        $images->publish($image);
        UpdateFleet::dispatch($image);

        return response()->json(['ok' => true]);
    }
}
