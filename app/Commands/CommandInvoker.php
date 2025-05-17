<?php

namespace App\Commands;

class CommandInvoker
{
    /**
     * Execute a command
     *
     * @param  CommandInterface  $command  The command to execute
     * @return mixed The result of the command execution
     */
    public function execute(CommandInterface $command)
    {
        return $command->execute();
    }

    /**
     * Execute multiple commands
     *
     * @param  array  $commands  An array of CommandInterface objects
     * @return array Results of each command execution
     */
    public function executeAll(array $commands): array
    {
        $results = [];

        foreach ($commands as $key => $command) {
            if (! $command instanceof CommandInterface) {
                throw new \InvalidArgumentException(
                    "Command at index {$key} is not a valid CommandInterface implementation"
                );
            }

            $results[$key] = $command->execute();
        }

        return $results;
    }
}
