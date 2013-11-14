<?php

namespace AmericanReading\PdfExtractor;

interface ConfigInterface
{
    const NAME = 'PDF Extractor';
    const COPYRIGHT = 'Copyright © 2013 by American Reading Company';
    const VERSION = '0.3.0';
    const PHAR_NAME = 'pdfextractor.phar';
    const CONFIGURATION_FILE_NAME = 'pdfextractor.json';
    const GITHUB_REPOSITORY_OWNER = 'AmericanReading';
    const GITHUB_REPOSITORY_NAME = 'pdfextractor';
    const GITHUB_ASSET_NAME = 'pdfextractor.phar';

    // Verbosity Levels
    const VERBOSITY_DEBUG = 2;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_SILENT = -1;
}
