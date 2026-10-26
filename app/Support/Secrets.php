<?php

namespace App\Support;

use Illuminate\Support\Str;

class Secrets
{
    public function database(): string
    {
        return Str::password(32, letters: true, numbers: true, symbols: false);
    }

    public function admin(): string
    {
        return Str::password(14, letters: true, numbers: true, symbols: false);
    }

    public function appKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }
}
