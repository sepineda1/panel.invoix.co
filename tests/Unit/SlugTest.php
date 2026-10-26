<?php

use App\Support\Slug;

it('sugiere un subdominio sin tildes, eñe ni espacios', function () {
    expect(Slug::suggest('Factura Mía'))->toBe('facturamia');
});

it('acepta un subdominio con guiones internos', function () {
    expect(Slug::error('factura-mia'))->toBeNull();
});

it('rechaza los subdominios reservados', function (string $slug) {
    expect(Slug::error($slug))->toBe('Ese subdominio está reservado.');
})->with([
    'www', 'panel', 'admin', 'api', 'mail', 'smtp', 'ftp',
    'ns1', 'ns2', 'invoix', 'app', 'cdn', 'status', 'localhost',
]);

it('rechaza mayúsculas, guion en los extremos, símbolos y longitudes inválidas', function (string $slug) {
    expect(Slug::error($slug))->not->toBeNull();
})->with([
    'Factura', 'fa', 'a', '-factura', 'factura-', 'factura_mia', 'factur.mia', 'ñoño', str_repeat('a', 33),
]);

it('acepta el mínimo y el máximo de caracteres', function () {
    expect(Slug::error('abc'))->toBeNull()
        ->and(Slug::error(str_repeat('a', 32)))->toBeNull();
});
