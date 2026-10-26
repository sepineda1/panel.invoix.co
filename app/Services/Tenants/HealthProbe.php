<?php

namespace App\Services\Tenants;

interface HealthProbe
{
    public function waitUntilUp(string $slug, int $seconds = 120): void;
}
