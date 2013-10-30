<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\App\AppException;

/**
 * Command subclass that throws an AppException to warn about ImageMagick availablity errors.
 */
abstract class ImageMagickCommand extends ConfiguredCommand
{
    const IM_CONVERT = 'convert';
    const IM_IDENTIFY = 'identify';

    public function run()
    {
        parent::run();
        if ($this->getStatusCode() === 127) {
            throw new AppException('ImageMagick command resulted with an error. Is ImageMagick installed and configured in the settings?');
        }
    }

    /**
     * @throws AppException
     * @return array A list of arguments most ImageMagick commands will use.
     */
    protected function getCommonArguments()
    {
        $args = array();

        $box = $this->configuration->get("box");
        if ($box) {
            switch ($box) {
                case 'crop':
                case 'trim':
                    $args[] =  "-define pdf:use-${box}box=true";
                    break;
                case 'media':
                    // Default. Do nothing.
                    break;
                default:
                    throw new AppException("Unexpected value for box \"$box\". Must by \"crop\", \"media\", or \"trim\".");
            }
        }

        $density = $this->configuration->get("density");
        if ($density !== null) {
            $args[] = "-density $density";
        }

        return $args;
    }
}
