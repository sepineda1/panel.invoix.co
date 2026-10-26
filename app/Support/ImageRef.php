<?php

namespace App\Support;

class ImageRef
{
    public const PATTERN = '/^ghcr\.io\/[a-z0-9]+(?:[._-][a-z0-9]+)*\/[a-z0-9]+(?:[._-][a-z0-9]+)*:[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$/';

    public static function valid(string $ref): bool
    {
        return preg_match(self::PATTERN, $ref) === 1;
    }
}
