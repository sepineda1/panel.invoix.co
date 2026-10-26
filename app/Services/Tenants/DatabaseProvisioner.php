<?php

namespace App\Services\Tenants;

interface DatabaseProvisioner
{
    public function ensure(string $database, string $user, string $password): void;

    public function drop(string $database, string $user): void;
}
