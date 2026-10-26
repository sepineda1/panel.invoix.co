<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use Inertia\Inertia;
use Inertia\Response;

class OperationController extends Controller
{
    public function index(): Response
    {
        $operations = Operation::query()->with('operator')->latest()->limit(200)->get()->map->present();

        return Inertia::render('Operations/Index', [
            'operations' => $operations,
        ]);
    }
}
