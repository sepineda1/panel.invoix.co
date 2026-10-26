<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Provisioning = 'provisioning';
    case Running = 'running';
    case Updating = 'updating';
    case Failed = 'failed';
    case Stopped = 'stopped';
    case Deleting = 'deleting';

    public function label(): string
    {
        return match ($this) {
            self::Provisioning => 'Creando',
            self::Running => 'En línea',
            self::Updating => 'Actualizando',
            self::Failed => 'Falló',
            self::Stopped => 'Detenida',
            self::Deleting => 'Eliminando',
        };
    }
}
