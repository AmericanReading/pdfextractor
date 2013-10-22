<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliApp\CliAppException;

/**
 * Command subclass that throws an exception to give the user a warning about ImageMagick not being
 * installed properly upon a non-zero status code.
 */
class ImageMagickCommand extends Command
{
    public function run()
    {
        parent::run();
        if ($this->getStatusCode() === 127) {
            throw new CliAppException('ImageMagick command resulted with an error. Is ImageMagick installed and configured in the settings?', 1);
        }
    }
}
