<?php

define("PHAR_NAME", "pdfextractor.phar");

$srcRoot = dirname(__FILE__) . "/src";
$buildRoot = dirname(__FILE__) . "/build";

$phar = new Phar($buildRoot . "/" . PHAR_NAME,
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    PHAR_NAME);
$phar->buildFromDirectory($srcRoot);
$phar->setStub($phar->createDefaultStub("run.php"));
