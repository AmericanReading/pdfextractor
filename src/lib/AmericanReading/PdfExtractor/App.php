<?php

namespace AmericanReading\PdfExtractor;

use AmericanReading\CliApp\CliApp;
use AmericanReading\CliApp\CliAppException;
use AmericanReading\Configuration\Configuration;
use Ulrichsg\Getopt;
use UnexpectedValueException;

class App extends CliApp implements ConfigInterface
{
    /** @var Configuration */
    private $conf;

    public function main($options = null)
    {
        $this->readConfiguration();
        $this->readOptions($options);
        $this->message("PDF Extractor\n", self::VERBOSITY_VERBOSE);
    }

    private function readConfiguration()
    {
        $this->conf = new Configuration();

        // Read the default configurations into the Configuration instance.
        $configurations  = array(
            Util::joinPaths("phar://" . self::PHAR_NAME, self::CONFIGURATION_FILE_NAME),
            Util::joinPaths(getenv("HOME"), self::CONFIGURATION_FILE_NAME),
            Util::joinPaths(getcwd(), self::CONFIGURATION_FILE_NAME)
        );
        foreach ($configurations as $conf) {
            if (file_exists($conf)) {
                $conf = file_get_contents($conf);
                $conf = Util::stripJsonComments($conf);
                $conf = json_decode($conf);
                $this->conf->load($conf);
            }
        }
    }

    private function readOptions($options = null)
    {
        // Build the options.
        $getopt = new Getopt(array(
            array('b', 'insert-blank',  Getopt::OPTIONAL_ARGUMENT, "Insert blank pages at the given locations."),
            array('c', 'configuration', Getopt::OPTIONAL_ARGUMENT, "JSON file of configuration options."),
            array('h', 'help',          Getopt::NO_ARGUMENT,       "Show this help message."),
            array('v', 'verbose',       Getopt::NO_ARGUMENT,       "Verbose mode.")
        ));

        // Parse.
        try {
            $getopt->parse($options);
        } catch (UnexpectedValueException $e) {
            throw new CliAppException($e->getMessage() . "\nTry --help for a list of options.", 1);
        }

        // Help. Show help and exit.
        if ($getopt->getOption('help')) {
            $getopt->showHelp();
            exit(0);
        }

        // Check if a configuration file was specified.
        if ($getopt->getOption('configuration')) {
            $configuration = $getopt->getOption('configuration');
            if (file_exists(realpath($configuration))) {
                $conf = file_get_contents(realpath($configuration));
                $conf = Util::stripJsonComments($conf);
                $conf = json_decode($conf);
                $this->conf->load($conf);
            } else {
                $this->errorWrite("Unable to read configuration file '$configuration'.\n");
            }
        }

        // Update the app configuration.
        if ($getopt->getOptions('verbose') !== null) {
            $this->conf->set('verbose', $getopt->getOptions('verbose'));
        }

        // Read the configuration.
        if ($this->conf->get('verbose', false)) {
            $this->verbosity = self::VERBOSITY_VERBOSE;
        }
    }
}
