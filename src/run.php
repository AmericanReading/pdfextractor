<?php

use AmericanReading\PdfExtractor\App;

define("PHAR_NAME", "pdfextractor.phar");

require_once("phar://" . PHAR_NAME . "/vendor/autoload.php");

$app = new App();
$app->run();
