<?php

namespace App\Support;

class TenantNames
{
    public function __construct(private readonly string $domain = 'invoix.co') {}

    public static function make(): self
    {
        return new self((string) config('panel.domain', 'invoix.co'));
    }

    public function database(string $slug): string
    {
        return 'invoix_'.$slug;
    }

    public function user(string $slug): string
    {
        return 'invoix_'.$slug;
    }

    public function redisPrefix(string $slug): string
    {
        return $slug.'-';
    }

    public function appUrl(string $slug): string
    {
        return 'https://'.$slug.'.'.$this->domain;
    }
}
