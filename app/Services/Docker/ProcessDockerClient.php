<?php

namespace App\Services\Docker;

use Symfony\Component\Process\Process;

class ProcessDockerClient implements DockerClient
{
    public function compose(string $composeFile, array $arguments, ?string $project = null): CommandResult
    {
        return $this->run(ComposeCommand::make($composeFile, $arguments, $project));
    }

    public function run(array $arguments): CommandResult
    {
        $process = new Process($arguments);
        $process->setTimeout(600);
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return new CommandResult($process->isSuccessful(), $output, $process->getExitCode() ?? 1);
    }
}
