<?php

use App\Support\TenantNames;

it('asigna base, usuario, prefijo de redis y url', function () {
    $names = new TenantNames('invoix.co');

    expect($names->database('factura-mia'))->toBe('invoix_factura-mia')
        ->and($names->user('factura-mia'))->toBe('invoix_factura-mia')
        ->and($names->redisPrefix('factura-mia'))->toBe('factura-mia-')
        ->and($names->appUrl('factura-mia'))->toBe('https://factura-mia.invoix.co');
});
