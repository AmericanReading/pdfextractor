<?php

namespace AmericanReading\PdfExtractor\Data;

class PdfInfo
{
    private $pageCount;
    private $pageSizes;

    public function __construct($pages)
    {
        $this->pageCount = count($pages);
        $this->pageSizes = array_map(function ($line) {
                list($w, $h) = explode(' ', $line);
                return (object) array(
                    'width' => $w,
                    'height' => $h
                );
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
}
