<?php

namespace App\Services\Docker;

class FakeDockerClient implements DockerClient
{
    /** @var list<array{command: list<string>, arguments: list<string>, file: ?string}> */
    public array $calls = [];

    public function compose(string $composeFile, array $arguments, ?string $project = null): CommandResult
    {
        $command = ComposeCommand::make($composeFile, $arguments, $project);
        $this->calls[] = ['command' => $command, 'arguments' => $arguments, 'file' => $composeFile];

        return new CommandResult(true, 'ok', 0);
    }

    public function run(array $arguments): CommandResult
    {
        $this->calls[] = ['command' => $arguments, 'arguments' => $arguments, 'file' => null];

        return new CommandResult(true, 'ok', 0);
    }
}
