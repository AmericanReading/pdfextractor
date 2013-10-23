<?php

use AmericanReading\PdfExtractor\MyApp;

define("PHAR_NAME", "pdfextractor.phar");

require_once("phar://" . PHAR_NAME . "/vendor/autoload.php");

$app = new MyApp();
$app->run();
