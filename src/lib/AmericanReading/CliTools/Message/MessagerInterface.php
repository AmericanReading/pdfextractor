<?php

namespace AmericanReading\CliTools\Message;

interface MessagerInterface
{
    /**
     * @param string $message The message to output
     * @param int $verbosity The level of verbosity
     */
    public function write($message, $verbosity=0);
}
