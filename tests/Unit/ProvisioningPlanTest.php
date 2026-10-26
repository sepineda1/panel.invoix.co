<?php

use App\Services\Docker\ComposeCommand;
use App\Support\ProvisioningPlan;

it('ordena los pasos del aprovisionamiento', function () {
    expect(ProvisioningPlan::STEPS)->toBe([
        'database', 'env', 'storage', 'compose', 'up', 'health', 'finish',
    ]);
});

it('empieza desde el principio si no hay un paso completado', function () {
    expect(ProvisioningPlan::remaining(null))->toBe(ProvisioningPlan::STEPS)
        ->and(ProvisioningPlan::remaining(''))->toBe(ProvisioningPlan::STEPS);
});

it('reanuda después del último paso completado', function () {
    expect(ProvisioningPlan::remaining('storage'))->toBe(['compose', 'up', 'health', 'finish'])
        ->and(ProvisioningPlan::remaining('finish'))->toBe([]);
});

it('no se salta pasos si el punto de reanudación no existe', function () {
    expect(ProvisioningPlan::remaining('desconocido'))->toBe(ProvisioningPlan::STEPS);
});

it('arma docker compose como lista de argumentos', function () {
    $command = ComposeCommand::make(
        '/var/lib/invoix/tenants/gogo/docker-compose.yml',
        ['up', '-d', '--remove-orphans'],
        'invoix-gogo',
    );

    expect($command)->toBe([
        'docker', 'compose', '-f', '/var/lib/invoix/tenants/gogo/docker-compose.yml',
        '-p', 'invoix-gogo', 'up', '-d', '--remove-orphans',
    ]);
});
