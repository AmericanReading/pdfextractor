<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Command\Command;
use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\CliTools\Message\MessagerInterface;
use AmericanReading\PdfExtractor\ConfigInterface;

class ConfiguredCommand extends Command implements ConfigInterface
{
    /** @var ReadableConfigurationInterface */
    private $conf;
    /** @var MessagerInterface */
    private $msg;

    public function __construct($command, $arguments = "", ReadableConfigurationInterface $conf)
    {
        parent::__construct($command, $arguments);
        $this->conf = $conf;
    }

    protected function buildCommandLine()
    {
        $cmd = $this->getCommand();

        // If the configuration is set, attempt to read the command from the configuration.
        if ($this->conf) {
            $confCmd = $this->conf->get($cmd);
            if ($confCmd !== null) {
                $cmd = $confCmd;
            }
        }

        return $cmd . ' ' . $this->getArguments();
    }

    /**
     * @param MessagerInterface $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    protected function write($message, $verbosity = self::VERBOSITY_NORMAL)
    {
        if (isset($this->msg)) {
            $this->msg->write($message, $verbosity);
        }
    }
}
