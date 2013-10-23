<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\PdfExtractor\Data\PdfInfo;

class ReadPdfInfoCommand extends ImageMagickCommand
{
    private $info;

    public function __construct($pdf, ReadableConfigurationInterface $conf)
    {
        $args = '-format "%W %H\n" ' . $pdf . ' 2> /dev/null';
        parent::__construct(self::IM_IDENTIFY, $args, $conf);
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
