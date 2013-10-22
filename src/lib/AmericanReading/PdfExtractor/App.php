<?php

namespace AmericanReading\PdfExtractor;

use AmericanReading\CliApp\CliApp;
use AmericanReading\CliApp\CliAppException;
use AmericanReading\Configuration\Configuration;
use AmericanReading\Configuration\ReadableConfigurationInterface;
use AmericanReading\PdfExtractor\Command\ImageMagickCommand;
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
        $this->readSourcePdf();

    }

    /**
     * @return ReadableConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->conf;
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
            array('b',  'blank',   Getopt::REQUIRED_ARGUMENT, "Insert blank pages at the given locations."),
            array('c',  'config',  Getopt::REQUIRED_ARGUMENT, "JSON file of configuration options."),
            array(null, 'debug',   Getopt::NO_ARGUMENT,       "Show debugging information."),
            array('h',  'help',    Getopt::NO_ARGUMENT,       "Show this help message."),
            array('s',  'source',  Getopt::REQUIRED_ARGUMENT, "Path to the PDF to process."),
            array('v',  'verbose', Getopt::NO_ARGUMENT,       "Verbose mode.")
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
        if ($getopt->getOption('config')) {
            $configuration = $getopt->getOption('config');
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

        // Debug
        if ($getopt->getOptions('debug') !== null) {
            $this->conf->set('debug', $getopt->getOption('debug'));
        }

        // Verbose
        if ($getopt->getOptions('verbose') !== null) {
            $this->conf->set('verbose', $getopt->getOption('verbose'));
        }

        // Source
        if ($getopt->getOptions('source') !== null) {
            $this->conf->set('source', $getopt->getOption('source'));
        }

        // Read the configuration.
        $this->debugMode = $this->conf->get('debug', false);

        if ($this->conf->get('verbose', false)) {
            $this->verbosity = self::VERBOSITY_VERBOSE;
        }
    }

    private function readSourcePdf()
    {
        $source = $this->conf->get('source');
        if ($source === null) {
            throw new CliAppException("No source file provided. Please specify the path to a PDF with -s or --source.", 1);
        }
        if (!file_exists($source)) {
            throw new CliAppException("Source file '$source' not found.'", 1);
        }

        $this->message("Reading $source...\n");

        $cmd = new ImageMagickCommand(
            self::IM_IDENTIFY,
            '-format "%W %H\n" ' . $source,
            $this->getConfiguration());
        $cmd->run();
        print_r($cmd->getResults());
    }
}
