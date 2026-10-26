<?php

namespace App\Services\Docker;

class CommandResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $output,
        public readonly int $exitCode,
    ) {}
}
