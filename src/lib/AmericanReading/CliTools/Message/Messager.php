<?php

namespace AmericanReading\CliTools\Message;

use InvalidArgumentException;

class Messager implements MessagerInterface
{
    private $defaultVerbosity = 0;
    private $verbosity = 0;
    private $handle;

    /**
     * @param resource $handle
     * @throws InvalidArgumentException
     */
    public function __construct($handle)
    {
        $meta = stream_get_meta_data($handle);
        if (!is_null($meta) && isset($meta['mode']) && strpos($meta['mode'], 'w') !== false) {
            $this->handle = $handle;
        } else {
            throw new InvalidArgumentException('Expected writable stream resource.');
        }
    }

    /**
     * @param string $message The message to output
     * @param int $verbosity The level of verbosity
     */
    public function write($message, $verbosity = null)
    {
        // Check if the caller supplied a verbosity level for the message.
        // If not, assume the application default.
        if (is_null($verbosity)) {
            $verbosity = $this->defaultVerbosity;
        }

        // If this message's verbosity level is at least as high as the
        // applcation verbosity level, display the message.
        if ($this->verbosity >= $verbosity) {
            fwrite($this->handle, $message);
        }
    }

    /**
     * @param int $verbosity
     */
    public function setVerbosity($verbosity)
    {
        $this->verbosity = $verbosity;
    }

    /**
     * @return int
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }
}
