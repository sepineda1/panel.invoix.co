<?php

namespace App\Enums;

enum OperationType: string
{
    case Create = 'create';
    case Update = 'update';
    case Restart = 'restart';
    case Deactivate = 'deactivate';
    case Activate = 'activate';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Crear',
            self::Update => 'Actualizar',
            self::Restart => 'Reiniciar',
            self::Deactivate => 'Desactivar',
            self::Activate => 'Activar',
            self::Delete => 'Eliminar',
        };
    }
}
