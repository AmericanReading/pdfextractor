<?php

use AmericanReading\Ebook\App;

require_once("phar://ebook.phar/vendor/autoload.php");

$app = new App();
$app->run();
