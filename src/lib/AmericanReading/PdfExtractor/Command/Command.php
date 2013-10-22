<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\Configuration\ReadableConfigurationInterface;
use UnexpectedValueException;

class Command
{
    /** @var  ReadableConfigurationInterface */
    private $configuration;
    private $command;
    private $commandArgs;
    private $statusCode;
    private $results;
    private $commandLine;

    public function __construct($command, $args, ReadableConfigurationInterface $configuration)
    {
        $this->command = $command;
        $this->commandArgs = $args;
        $this->configuration = $configuration;
    }

    public function run()
    {
        $cmd = $this->configuration->get($this->command);
        if ($cmd === null) {
            throw new UnexpectedValueException("Unable to locate $this->command in configuration");
        }
        $cmd .= ' ' . $this->commandArgs;

        $this->commandLine = $cmd;
        exec($this->commandLine, $this->results, $this->statusCode);
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }

    /**
     * @param ReadableConfigurationInterface $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return ReadableConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $args
     */
    public function setArgs($args)
    {
        $this->commandArgs = $args;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->commandArgs;
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

}
