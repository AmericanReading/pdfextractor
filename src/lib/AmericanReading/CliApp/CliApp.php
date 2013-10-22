<?php

namespace AmericanReading\CliApp;

/**
 * Abstract base class for command line interface applications.
 *
 * Defines some functionality for reading options and sending messages.
 */
abstract class CliApp
{
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_SILENT = -1;

    public $catchCliAppExcpetion = true;
    protected $debugMode = false;
    protected $debugPattern = '[DEBUG] %s';
    protected $stderr;
    protected $stdin;
    protected $stdout;
    protected $verbosityMessageDefault = self::VERBOSITY_NORMAL;
    protected $verbosity = self::VERBOSITY_NORMAL;

    /**
     * Create a new instance of the application
     */
    public function __construct()
    {
        $this->stderr = fopen('php://stderr', 'w');
        $this->stdout = fopen('php://stdout', 'w');
    }

    public function __destruct()
    {
        fclose($this->stderr);
        fclose($this->stdout);
    }

    public function run($options = null)
    {
        if ($this->catchCliAppExcpetion) {
            try {
                $this->main($options);
            } catch (CliAppException $e) {
                $this->errorWrite($e->getMessage() . "\n");
                exit($e->getCode());
            }
        } else {
            $this->main($options);
        }
    }

    /**
     * Write a message, prefix with the debug prefix, to the STD out.
     * Only do this if the application is running in debug mode.
     *
     * @param string $message
     */
    protected function debugMessage($message)
    {
        if ($this->debugMode) {
            $this->messageWrite(sprintf($this->debugPattern, $message));
        }
    }

    /**
     * Write this message to the standard error.
     *
     * @param string $message
     */
    protected function errorWrite($message)
    {
        fwrite($this->stderr, $message);
    }

    /**
     * Write this message to the standard out.
     *
     * @param string $message
     */
    protected function messageWrite($message)
    {
        fwrite($this->stdout, $message);
    }

    /**
     * Write the message to the standard out as long as the application is
     * running in a high-enough verbosity mode.
     *
     * @param string $message
     * @param int $messageVerbosity Level of verbosity for the message
     */
    protected function message($message, $messageVerbosity = null)
    {
        // Check if the caller supplied a verbosity level for the message.
        // If not, assume the application default.
        if (is_null($messageVerbosity)) {
            $messageVerbosity = $this->verbosityMessageDefault;
        }

        // If this message's verbosity level is at least as high as the
        // applcation verbosity level, display the message.
        if ($this->verbosity >= $messageVerbosity) {
            $this->messageWrite($message);
        }
    }

    abstract protected function main($options = null);
}
