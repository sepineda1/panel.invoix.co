<?php

namespace App\Services\Docker;

class ComposeCommand
{
    /**
     * Lista de argumentos para Symfony Process. No hay shell de por medio.
     *
     * @param  list<string>  $arguments
     * @return list<string>
     */
    public static function make(string $composeFile, array $arguments, ?string $project = null): array
    {
        $command = ['docker', 'compose', '-f', $composeFile];

        if ($project !== null) {
            $command[] = '-p';
            $command[] = $project;
        }

        return array_merge($command, $arguments);
    }
}
