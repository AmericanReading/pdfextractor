<?php

namespace AmericanReading\PdfExtractor;

use AmericanReading\CliTools\App\App;
use AmericanReading\CliTools\App\AppException;
use AmericanReading\CliTools\Command\Command;
use AmericanReading\CliTools\Message\Messager;
use AmericanReading\CliTools\Message\MessagerInterface;
use AmericanReading\CliTools\Configuration\Configuration;
use Ulrichsg\Getopt;
use UnexpectedValueException;

class MyApp extends App implements ConfigInterface
{
    /** @var Configuration */
    private $conf;
    /** @var MessagerInterface  */
    private $msg;
    /** @var MessagerInterface  */
    private $err;
    /** @var resource */
    private $stdout;
    /** @var resource */
    private $stderr;

    public function __construct()
    {
        $this->stdout = fopen('php://stdout', 'w', self::VERBOSITY_NORMAL);
        $this->msg = new Messager($this->stdout);

        $this->stderr = fopen('php://stderr', 'w', self::VERBOSITY_SILENT);
        $this->err = new Messager($this->stderr);
    }

    public function __destruct()
    {
        fclose($this->stdout);
        fclose($this->stderr);
    }

    protected function main($options = null)
    {
        $this->readConfiguration();
        $this->readOptions($options);
        $this->msg->write("PDF Extractor\n", self::VERBOSITY_VERBOSE);
        $this->readSourcePdf();
    }

    protected function exitWithError($statusCode = 1, $message = null)
    {
        if (!is_null($message)) {
            $this->err->write($message . "\n");
        }
        exit($statusCode);
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
                $this->loadConfiguration($conf);
            }
        }
    }

    private function readOptions($options = null)
    {
        // Build the options.
        $getopt = new Getopt(array(
            array('b',  'blank',   Getopt::REQUIRED_ARGUMENT, "Insert blank pages at the given locations."),
            array('c',  'config',  Getopt::REQUIRED_ARGUMENT, "JSON file of configuration options."),
            array('h',  'help',    Getopt::NO_ARGUMENT,       "Show this help message."),
            array('s',  'source',  Getopt::REQUIRED_ARGUMENT, "Path to the PDF to process."),
            array('v',  'verbose', Getopt::NO_ARGUMENT,       "Verbose mode.")
        ));

        // Parse.
        try {
            $getopt->parse($options);
        } catch (UnexpectedValueException $e) {
            throw new AppException($e->getMessage() . "\nTry --help for a list of options.");
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
                $this->loadConfiguration($configuration);
            } else {
                $this->err->write("Unable to read configuration file '$configuration'.\n");
            }
        }

        // Update the app configuration.

        // Verbose
        if ($getopt->getOptions('verbose') !== null) {
            $this->conf->set('verbose', $getopt->getOption('verbose'));
        }

        // Source
        if ($getopt->getOptions('source') !== null) {
            $this->conf->set('source', $getopt->getOption('source'));
        }

        if ($this->conf->get('verbose', false)) {
            $this->msg->setVerbosity(self::VERBOSITY_VERBOSE);
        }
    }

    /**
     * Read a configruation file, strip comments, decode JSON, and merge into configuration.
     *
     * @param string $filePath Path to configuration file.
     */
    private function loadConfiguration($filePath)
    {
        $conf = file_get_contents($filePath);
        $conf = Util::stripJsonComments($conf);
        $conf = json_decode($conf);
        $this->conf->load($conf);
    }

    private function readSourcePdf()
    {
        $source = $this->conf->get('source');

        if ($source === null) {
            throw new AppException("No source file provided. Please specify the path to a PDF with -s or --source.");
        }
        if (!file_exists($source)) {
            throw new AppException("Source file '$source' not found.'");
        }

        $this->msg->write("Reading $source...\n");

        $cmd = new Command(
            self::IM_IDENTIFY,
            '-format "%W %H\n" ' . $source);

        $cmd->run();
        print_r($cmd->getResults());

    }
}
