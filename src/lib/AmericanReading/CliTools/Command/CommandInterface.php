<?php

namespace AmericanReading\CliTools\Command;

interface CommandInterface
{
    /**
     * Execute the command. This will set commandLine, results, and statusCode.
     */
    public function run();

    /**
     * @return string The last command issued to exec().
     */
    public function getCommandLine();

    /**
     * @return array Array of lines output by the command.
     */
    public function getResults();

    /**
     * @return int The exit status code from the last run command.
     */
    public function getStatusCode();
}
