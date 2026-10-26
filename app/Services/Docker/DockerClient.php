<?php

namespace App\Services\Docker;

interface DockerClient
{
    /**
     * @param  list<string>  $arguments
     */
    public function compose(string $composeFile, array $arguments, ?string $project = null): CommandResult;

    /**
     * @param  list<string>  $arguments
     */
    public function run(array $arguments): CommandResult;
}
