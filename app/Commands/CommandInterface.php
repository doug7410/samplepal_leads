<?php

namespace App\Commands;

interface CommandInterface
{
    /**
     * Execute the command
     *
     * @return mixed The result of the command execution
     */
    public function execute();
}
