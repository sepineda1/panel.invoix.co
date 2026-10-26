<?php

namespace App\Services\Tenants;

class FakeDatabaseProvisioner implements DatabaseProvisioner
{
    /** @var list<array{database: string, user: string, password: string}> */
    public array $ensured = [];

    /** @var list<array{database: string, user: string}> */
    public array $dropped = [];

    public function ensure(string $database, string $user, string $password): void
    {
        $this->ensured[] = compact('database', 'user', 'password');
    }

    public function drop(string $database, string $user): void
    {
        $this->dropped[] = compact('database', 'user');
    }
}
