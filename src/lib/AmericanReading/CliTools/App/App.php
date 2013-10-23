<?php

namespace AmericanReading\CliTools\App;

/**
 * Abstract base class for command line interface applications.
 *
 * Defines some functionality for reading options and sending messages.
 */
abstract class App
{
    public $catchCliAppExcpetion = true;

    public function run($options = null)
    {
        if ($this->catchCliAppExcpetion) {
            try {
                $this->main($options);
            } catch (AppException $e) {
                $this->exitWithError($e->getCode(), $e->getMessage());
            }
        } else {
            $this->main($options);
        }
    }

    abstract protected function main($options = null);
    abstract protected function exitWithError($statusCode = 1, $message = null);
}
