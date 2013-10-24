<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\PdfExtractor\Data\PdfInfo;

class ReadPdfInfoCommand extends ImageMagickCommand
{
    private $info;
    private $source;

    /**
     * @param string $source
     * @param ReadableConfigurationInterface $configuration
     */
    public function __construct($source, ReadableConfigurationInterface $configuration)
    {
        parent::__construct($configuration);
        $this->source = $source;
    }

    public function getCommandLine()
    {
        $cmd = array($this->configuration->get(self::IM_IDENTIFY));
        $cmd = array_merge($cmd, $this->getCommonArguments());
        $cmd[] = '-format "%W %H\n"';
        $cmd[] = '"' . $this->source . '"';
        return join(' ', $cmd);
    }

    public function run()
    {
        parent::run();
        $this->info = new PdfInfo($this->getResults());
    }

    /**
     * @return PdfInfo
     */
    public function getInfo()
    {
        return $this->info;
    }
}
