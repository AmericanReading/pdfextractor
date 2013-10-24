<?php

namespace AmericanReading\Geometry;

use UnexpectedValueException;

class Size
{
    public $width;
    public $height;

    public function __construct($width = 0, $height = 0)
    {
        if (!is_numeric($width) || !is_numeric($height)) {
            throw new UnexpectedValueException("Numeric values expected for width and height.");
        }
        $this->width = $width;
        $this->height = $height;
    }

    public static function initWithString($size)
    {
        $pattern = "/(?P<width>\d+)\D*(?P<height>\d+)/";
        if (preg_match($pattern, $size, $matches)) {
            return self::initWithArray($matches);
        }
        throw new UnexpectedValueException("Unable to parse string dimensions.");
    }

    public static function initWithArray($arr)
    {
        if (!isset($arr['width'], $arr['height'])) {
            throw new UnexpectedValueException("Array must conatin \"width\" and \"height\" members.");
        }
        return new self($arr['width'], $arr['height']);
    }

    public function __toString()
    {
        return $this->width . 'x' . $this->height;
    }
}
