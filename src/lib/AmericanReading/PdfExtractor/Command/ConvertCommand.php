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

    public function __construct($source, $target, ReadableConfigurationInterface $conf)
    {
        $this->source = $source;
        $this->target = $target;

        parent::__construct(self::IM_CONVERT, '', $conf);
    }

    public function getArguments()
    {
        $args = array(
            '-define pdf:use-cropbox=true'
        );

        if (isset($this->cropSize)) {
            $crop = "-crop " . $this->cropSize->width . "x" . $this->cropSize->height;
            if (isset($this->cropOffset)) {
                $crop .= '+' . $this->cropOffset->x . '+' . $this->cropOffset->y;
            }
            $args[] = $crop;
        }

        $args[] = $this->source;
        $args[] = $this->target;

        return join(' ', $args);
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
