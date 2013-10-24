<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\Geometry\Point;
use AmericanReading\Geometry\Size;
use AmericanReading\PdfExtractor\Data\PdfInfo;

class ConvertCommand extends ImageMagickCommand
{
    private $source;
    private $target;
    private $cropSize;
    private $cropOffset;

    public function __construct($source, $target, ReadableConfigurationInterface $configuration)
    {
        parent::__construct($configuration);
        $this->source = $source;
        $this->target = $target;
    }

    public function getCommandLine()
    {
        $cmd = array($this->configuration->get(self::IM_CONVERT));
        $cmd = array_merge($cmd, $this->getCommonArguments());

        if (isset($this->cropSize)) {
            $crop = "-crop " . $this->cropSize->width . "x" . $this->cropSize->height;
            if (isset($this->cropOffset)) {
                $crop .= "+" . $this->cropOffset->x . "+" . $this->cropOffset->y;
            }
            $cmd[] = $crop;
        }

        $resize = $this->configuration->get("resize");
        if ($resize !== null) {
            $cmd[] = "-resize $resize";
        }

        $cmd[] = $this->source;
        $cmd[] = $this->target;

        $cmd[] = '2> /dev/null';
        return join(' ', $cmd);
    }

    /**
     * @param Point $cropOffset
     */
    public function setCropOffset(Point $cropOffset)
    {
        $this->cropOffset = $cropOffset;
    }

    /**
     * @return Point
     */
    public function getCropOffset()
    {
        return $this->cropOffset;
    }

    /**
     * @param Size $cropSize
     */
    public function setCropSize(Size $cropSize)
    {
        $this->cropSize = $cropSize;
    }

    /**
     * @return Size
     */
    public function getCropSize()
    {
        return $this->cropSize;
    }
}
