<?php

namespace App\Support;

class Slug
{
    /** @var list<string> */
    public const RESERVED = [
        'www', 'panel', 'admin', 'api', 'mail', 'smtp', 'ftp',
        'ns1', 'ns2', 'invoix', 'app', 'cdn', 'status', 'localhost',
    ];

    public static function suggest(string $name): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ];

        $value = mb_strtolower(strtr($name, $map));
        $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';

        return substr($value, 0, 32);
    }

    public static function error(string $slug): ?string
    {
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])$/', $slug)) {
            return 'El subdominio solo puede tener minúsculas, números y guiones, entre 3 y 32 caracteres, y no puede empezar ni terminar en guion.';
        }

        if (in_array($slug, self::RESERVED, true)) {
            return 'Ese subdominio está reservado.';
        }

        return null;
    }

    public static function valid(string $slug): bool
    {
        return self::error($slug) === null;
    }
}
