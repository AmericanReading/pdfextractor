<?php

namespace AmericanReading\PdfExtractor\Data;

class Dimension
{
    public $width;
    public $height;

    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function __toString()
    {
        return $this->width . 'x' . $this->height;
    }
}
