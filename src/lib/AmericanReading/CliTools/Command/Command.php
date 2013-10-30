<?php

namespace AmericanReading\CliTools\Command;

/**
 * Class for building and running commands throw exec().
 */
class Command extends CommandBase
{
    /** @var string The last command line sent to exec()  */
    private $commandLine;

    public function __construct($commandLine)
    {
        $this->setCommandLine($commandLine);
    }

    /**
     * @param string $commandLine
     */
    public function setCommandLine($commandLine)
    {
        $this->commandLine = $commandLine;
    }

    /**
     * @return string
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }
}
