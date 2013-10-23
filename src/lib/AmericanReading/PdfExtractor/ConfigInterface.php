<?php

namespace AmericanReading\PdfExtractor;

interface ConfigInterface
{
    const PHAR_NAME = 'pdfextractor.phar';
    const CONFIGURATION_FILE_NAME = 'pdfextractor.json';

    // Verbosity Levels
    const VERBOSITY_DEBUG = 2;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_SILENT = -1;
}
