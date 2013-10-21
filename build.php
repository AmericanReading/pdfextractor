<?php

$srcRoot = dirname(__FILE__) . "/src";
$buildRoot = dirname(__FILE__) . "/build";

$phar = new Phar($buildRoot . "/ebook.phar",
    FilesystemIterator::CURRENT_AS_FILEINFO |
        FilesystemIterator::KEY_AS_FILENAME,
    "ebook.phar");

$phar["index.php"] = file_get_contents($srcRoot . "/index.php");
$phar["common.php"] = file_get_contents($srcRoot . "/common.php");
$phar->setStub($phar->createDefaultStub("index.php"));

copy($srcRoot . "/config.ini", $buildRoot . "/config.ini");
