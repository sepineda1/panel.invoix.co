<?php

namespace App\Services\Tenants;

use PDO;
use RuntimeException;

class MysqlDatabaseProvisioner implements DatabaseProvisioner
{
    public function ensure(string $database, string $user, string $password): void
    {
        $this->assertIdentifier($database);
        $this->assertIdentifier($user);

        $pdo = $this->pdo();
        $quotedUser = $pdo->quote($user);
        $quotedPassword = $pdo->quote($password);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("CREATE USER IF NOT EXISTS {$quotedUser}@'%' IDENTIFIED BY {$quotedPassword}");
        $pdo->exec("ALTER USER {$quotedUser}@'%' IDENTIFIED BY {$quotedPassword}");
        $pdo->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO {$quotedUser}@'%'");
        $pdo->exec('FLUSH PRIVILEGES');
    }

    public function drop(string $database, string $user): void
    {
        $this->assertIdentifier($database);
        $this->assertIdentifier($user);

        $pdo = $this->pdo();
        $quotedUser = $pdo->quote($user);
        $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
        $pdo->exec("DROP USER IF EXISTS {$quotedUser}@'%'");
        $pdo->exec('FLUSH PRIVILEGES');
    }

    private function assertIdentifier(string $value): void
    {
        if (! preg_match('/^invoix_[a-z0-9-]{3,32}$/', $value)) {
            throw new RuntimeException('Nombre de base o usuario rechazado.');
        }
    }

    private function pdo(): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%s', config('panel.db_host'), config('panel.db_port')),
            (string) config('panel.db_root_user'),
            (string) config('panel.db_root_password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }
}
