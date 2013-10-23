<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\App\AppException;

/**
 * Command subclass that throws an AppException to warn about ImageMagick availablity errors.
 */
class ImageMagickCommand extends ConfiguredCommand
{
    const IM_IDENTIFY = 'identify';

    public function run()
    {
        parent::run();
        if ($this->getStatusCode() === 127) {
            throw new AppException('ImageMagick command resulted with an error. Is ImageMagick installed and configured in the settings?');
        }
    }
}
