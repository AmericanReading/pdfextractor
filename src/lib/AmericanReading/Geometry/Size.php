<?php

namespace AmericanReading\Geometry;

class Size
{
    public $width;
    public $height;

    public function __construct($width = 0, $height = 0)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function __toString()
    {
        return $this->width . 'x' . $this->height;
    }
}
