<?php

namespace App\Support;

class ProvisioningPlan
{
    /** @var list<string> */
    public const STEPS = [
        'database',
        'env',
        'storage',
        'compose',
        'up',
        'health',
        'finish',
    ];

    /**
     * Pasos que faltan después del último que terminó bien.
     * Un punto desconocido vuelve a empezar, para no saltarse trabajo.
     *
     * @return list<string>
     */
    public static function remaining(?string $checkpoint): array
    {
        if ($checkpoint === null || $checkpoint === '') {
            return self::STEPS;
        }

        $index = array_search($checkpoint, self::STEPS, true);

        if ($index === false) {
            return self::STEPS;
        }

        return array_values(array_slice(self::STEPS, $index + 1));
    }
}
