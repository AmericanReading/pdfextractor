<?php

namespace AmericanReading\CliTools\App;

use Exception;

/**
 * Exception for terminating a CliApp instance. All CliAppExceptions thrown inside CliApp::run()
 * will be (by default) caught and, and the app will exit, outputing the message and using the code
 * as the exit code.
 *
 * Class CliAppException
 * @package AmericanReading\CliApp
 */
class AppException extends Exception
{
    public function __construct($message = "", $code = 1, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
