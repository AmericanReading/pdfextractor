<?php

namespace AmericanReading\Ebook;

use Ulrichsg\Getopt;

class App
{
    public function run()
    {
        echo "Ebook Utility";
        $this->readOptions();
    }

    private function readOptions()
    {
        $getopt = new Getopt(array(
            array('b', 'insert-blank',  Getopt::OPTIONAL_ARGUMENT,
                "Insert blank pages at the given locations."),
            array('h', 'help', Getopt::NO_ARGUMENT, "Show this help message.")
        ));

        $getopt->parse();

        if ($getopt->getOption('help')) {
            $getopt->showHelp();
            exit(0);
        }

        echo $getopt->getOption('insert-blank');
        exit(0);

    }
}
