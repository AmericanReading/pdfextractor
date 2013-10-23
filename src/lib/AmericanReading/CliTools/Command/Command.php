<?php

namespace AmericanReading\CliTools\Command;

/**
 * Class for building and running commands throw exec().
 */
class Command implements CommandInterface
{
    /** @var string Path to the command to execute */
    private $command;
    /** @var string Optional argumets to send to the command */
    private $arguments;
    /** @var string The last command line sent to exec()  */
    private $commandLine;
    /** @var int The exit code from the last command sent to exec() */
    private $statusCode;
    /** @var array List of lines output from the last command sent to exec() */
    private $results;

    /**
     * @param string $command Path to the command to execute
     * @param string $arguments Arguments to send to the command
     */
    public function __construct($command, $arguments='')
    {
        $this->setCommand($command);
        $this->setArguments($arguments);
    }

    /**
     * Execute the command. This will set results, and statusCode.
     */
    public function run()
    {
        exec($this->getCommandLine(), $this->results, $this->statusCode);
    }

    /**
     * @param string $arguments
     */
    public function setArguments($arguments)
    {
        unset($this->commandLine);
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        unset($this->commandLine);
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getCommandLine()
    {
        if (!isset($this->commandLine)) {
            $this->commandLine = $this->buildCommandLine();
        }
        return $this->commandLine;
    }

    /**
     * @return array Array of lines output by the command.
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return int The exit status code from the last run command.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string The full command line to run using exec()
     */
    protected function buildCommandLine()
    {
        return $this->getCommand() . ' ' . $this->getArguments();
    }
}
