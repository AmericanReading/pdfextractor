<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\PdfExtractor\Data\PdfInfo;

class ReadPdfInfoCommand extends ImageMagickCommand
{
    private $info;
    private $source;

    public function __construct($souce, ReadableConfigurationInterface $configuration)
    {
        parent::__construct($configuration);
        $this->source = $souce;
    }

    public function getCommandLine()
    {
        $cmd = array($this->configuration->get(self::IM_IDENTIFY));
        $cmd = array_merge($cmd, $this->getCommonArguments());
        $cmd[] = '-format "%W %H\n"';
        $cmd[] = $this->source;
        $cmd[] = '2> /dev/null';
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
