<?php

declare(strict_types=1);

namespace Pds\Skeleton;

final class Console
{
    public const VERSION = 2.0;
    public const PATCH = 0;

    private $commandsWhitelist =
        [
            'validate' => 'Pds\Skeleton\ComplianceValidator',
            'generate' => 'Pds\Skeleton\PackageGenerator',
        ];

    public function execute(string ...$args) : bool
    {
        if (\count($args) > 1) {
            $executable = \array_shift($args);
            $commandName = \array_shift($args);

            if (\array_key_exists($commandName, $this->commandsWhitelist)) {
                $result = $this->executeCommand($this->commandsWhitelist[$commandName], $args);
            }

            $result = false;
        }

        if($result === false) {
            $this->outputHelp();
        }

        return $result;
    }

    private function executeCommand(string $commandClass, iterable $args) : bool
    {
        $command = new $commandClass();

        return $command->execute(...$args);
    }

    private function outputHelp() : void
    {
        echo 'Available commands:' . PHP_EOL;
        foreach ($this->commandsWhitelist as $key => $value) {
            echo 'pds-skeleton ' . $key . PHP_EOL;
        }
    }
}
