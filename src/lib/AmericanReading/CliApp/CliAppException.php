<?php

namespace AmericanReading\CliApp;

use Exception;

/**
 * Exception for terminating a CliApp instance. All CliAppExceptions thrown inside CliApp::run()
 * will be (by default) caught and, and the app will exit, outputing the message and using the code
 * as the exit code.
 *
 * Class CliAppException
 * @package AmericanReading\CliApp
 */
class CliAppException extends Exception
{
}
