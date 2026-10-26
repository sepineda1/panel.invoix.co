<?php

namespace App\Enums;

enum OperationStatus: string
{
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'En curso',
            self::Success => 'Correcta',
            self::Failed => 'Falló',
        };
    }
}
