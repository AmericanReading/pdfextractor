<?php

namespace AmericanReading\CliTools\Command;

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
        $this->command = $command;
        $this->arguments = $arguments;
    }

    /**
     * Execute the command. This will set commandLine, results, and statusCode.
     */
    public function run()
    {
        $cmd = $this->getCommand() . ' ' . $this->getArguments();
        $this->commandLine = $cmd;
        exec($this->commandLine, $this->results, $this->statusCode);
    }

    /**
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return mixed
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
}
