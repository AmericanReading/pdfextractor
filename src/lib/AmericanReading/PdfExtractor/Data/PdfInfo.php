<?php

namespace AmericanReading\PdfExtractor\Data;

use AmericanReading\Geometry\Size;

class PdfInfo
{
    const SPREAD_TOLERANCE = 0.8;

    private $pageCount;
    private $pageSizes;
    private $smallestPageSize;
    private $largestPageSize;

    public function __construct($pages)
    {
        $this->pageCount = count($pages);
        $this->pageSizes = array_map(function ($line) {
                list($width, $height) = explode(' ', $line);
                return new Size($width, $height);
            }, $pages);
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * @return array
     */
    public function getPageSizes()
    {
        return $this->pageSizes;
    }

    /**
     * @return bool All pages in the PDF are of identical size.
     */
    public function isUniform()
    {
        $width = $this->pageSizes[0]->width;
        $height = $this->pageSizes[0]->height;
        for ($i = 1; $i < $this->pageCount; $i++) {
            if ($width !== $this->pageSizes[$i]->width ||
                $height !== $this->pageSizes[$i]->height
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return a representation of the smallest width and height. Note that this may not reflect any
     * specific page as page 1 may have the smallest height while 2 has the smallest width.
     *
     * @return Size
     */
    public function getSmallestPageSize()
    {
        if (!isset($this->smallestPageSize)) {
            $width = $this->pageSizes[0]->width;
            $height = $this->pageSizes[0]->height;
            for ($i = 1; $i < $this->pageCount; $i++) {
                $width = min($width, $this->pageSizes[$i]->width);
                $height = min($height, $this->pageSizes[$i]->height);
            }
            $this->smallestPageSize = new Size($width, $height);


        }
        return $this->smallestPageSize;
    }

    /**
     * Return a representation of the largest width and height. Note that this may not reflect any
     * specific page as page 1 may have the largest height while 2 has the largest width.
     *
     * @return Size
     */
    public function getLargestPageSize()
    {
        if (!isset($this->largestPageSize)) {
            $width = $this->pageSizes[0]->width;
            $height = $this->pageSizes[0]->height;
            for ($i = 1; $i < $this->pageCount; $i++) {
                $width = max($width, $this->pageSizes[$i]->width);
                $height = max($height, $this->pageSizes[$i]->height);
            }
            $this->largestPageSize = new Size($width, $height);
        }
        return $this->largestPageSize;
    }

    /**
     * Compare the largest to smallest page and determine if the PDF likely contains some pages
     * which are 2-up spreads.
     */
    public function containsSpreads()
    {
        return $this->isSpread($this->getLargestPageSize());
    }

    /**
     * @param Size $pageSize The passed pageSize reflects a spread.
     * @return bool
     */
    public function isSpread(Size $pageSize)
    {
        $small = $this->getSmallestPageSize();
        return ($small->width * 2 * self::SPREAD_TOLERANCE <= $pageSize->width);
    }

}
