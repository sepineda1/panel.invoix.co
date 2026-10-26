<?php

namespace App\Jobs;

use App\Services\Tenants\FleetUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFleet implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;

    public int $tries = 1;

    public int $uniqueFor = 7200;

    public function __construct(public string $image, public ?int $operatorId = null) {}

    public function handle(FleetUpdater $updater): void
    {
        $updater->run($this->image, $this->operatorId);
    }
}
