<?php

namespace App\Services\Tenants;

class FakeHealthProbe implements HealthProbe
{
    /** @var list<string> */
    public array $calls = [];

    public function waitUntilUp(string $slug, int $seconds = 120): void
    {
        $this->calls[] = $slug;
    }
}
