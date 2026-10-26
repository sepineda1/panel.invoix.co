<?php

namespace App\Services\Tenants;

use App\Models\Setting;
use App\Support\ImageRef;
use InvalidArgumentException;

class PublishedImage
{
    public function current(): string
    {
        $stored = (string) Setting::getValue('published_image', '');

        return $stored !== '' ? $stored : (string) config('panel.image');
    }

    public function publish(string $image): void
    {
        if (! ImageRef::valid($image)) {
            throw new InvalidArgumentException('La referencia de la imagen no es válida.');
        }

        Setting::putValue('published_image', $image);
    }
}
