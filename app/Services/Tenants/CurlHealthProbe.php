<?php

namespace App\Services\Tenants;

use RuntimeException;
use Symfony\Component\Process\Process;

class CurlHealthProbe implements HealthProbe
{
    public function waitUntilUp(string $slug, int $seconds = 120): void
    {
        $url = $this->url($slug);
        $deadline = microtime(true) + $seconds;
        $last = 'sin respuesta';

        while (microtime(true) < $deadline) {
            $code = $this->request($url);
            if ($code === '200') {
                return;
            }

            $last = $code !== '' ? 'HTTP '.$code : 'sin respuesta';
            sleep(3);
        }

        throw new RuntimeException('El sitio no respondió 200 en /up ('.$last.').');
    }

    private function url(string $slug): string
    {
        if (config('panel.health_mode') === 'internal') {
            return 'http://'.$slug.'-web/up';
        }

        return 'https://'.$slug.'.'.config('panel.domain').'/up';
    }

    private function request(string $url): string
    {
        $command = ['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}', '--max-time', '10', $url];

        if (config('panel.health_mode') !== 'internal') {
            $host = parse_url($url, PHP_URL_HOST);
            $ip = gethostbyname((string) config('panel.caddy_host'));
            if (is_string($host) && $ip !== '' && $ip !== config('panel.caddy_host')) {
                $command = ['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}', '--max-time', '15', '--resolve', $host.':443:'.$ip, $url];
            }
        }

        $process = new Process($command);
        $process->setTimeout(20);
        $process->run();

        return trim($process->getOutput());
    }
}
