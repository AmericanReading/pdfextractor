<?php

$srcRoot = dirname(__FILE__) . "/src";
$buildRoot = dirname(__FILE__) . "/build";

$phar = new Phar($buildRoot . "/ebook.phar",
    FilesystemIterator::CURRENT_AS_FILEINFO |
        FilesystemIterator::KEY_AS_FILENAME,
    "ebook.phar");
$phar->buildFromDirectory($srcRoot);
$phar->setStub($phar->createDefaultStub("run.php"));
