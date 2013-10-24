<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;

class BlankPageCommand extends ImageMagickCommand
{
    private $size;
    private $target;

    /**
     * @param string $size
     * @param string $target
     * @param ReadableConfigurationInterface $configuration
     */
    public function __construct($size, $target, ReadableConfigurationInterface $configuration)
    {
        parent::__construct($configuration);
        $this->size = $size;
        $this->target = $target;
    }

    public function getCommandLine()
    {
        $cmd = array(
            $this->configuration->get(self::IM_CONVERT),
            "-size $this->size",
            "canvas:white",
        );

        $resize = $this->configuration->get("resize");
        if ($resize !== null) {
            $cmd[] = "-resize $resize";
        }

        $cmd[] = '"' . $this->target . '"';

        return join(' ', $cmd);
    }
}
