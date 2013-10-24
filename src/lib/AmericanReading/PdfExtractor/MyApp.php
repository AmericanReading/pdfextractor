<?php

namespace AmericanReading\PdfExtractor;

use AmericanReading\CliTools\App\App;
use AmericanReading\CliTools\App\AppException;
use AmericanReading\CliTools\Configuration\Configuration;
use AmericanReading\CliTools\Message\Messager;
use AmericanReading\CliTools\Message\MessagerInterface;
use AmericanReading\Geometry\Point;
use AmericanReading\PdfExtractor\Command\ConvertCommand;
use AmericanReading\PdfExtractor\Command\ReadPdfInfoCommand;
use AmericanReading\PdfExtractor\Data\PdfInfo;
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
    /** @var PdfInfo */
    private $pdfInfo;

    public function __construct()
    {
        $this->stdout = fopen('php://stdout', 'w');
        $this->msg = new Messager($this->stdout, self::VERBOSITY_NORMAL);

        $this->stderr = fopen('php://stderr', 'w');
        $this->err = new Messager($this->stderr, self::VERBOSITY_SILENT);
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
        $this->readPdfInfo();
        $this->outputPages();
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
            Util::pharPath(self::CONFIGURATION_FILE_NAME),
            Util::joinPaths(getenv("HOME"), '.' . self::CONFIGURATION_FILE_NAME),
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
            array('h',  'help',    Getopt::NO_ARGUMENT,       "Show this help message."),
            array('i',  'source',  Getopt::REQUIRED_ARGUMENT, "Path to the PDF to process."),
            array('o',  'target',  Getopt::REQUIRED_ARGUMENT, "Directory to write converted pages."),
            array('c',  'config',  Getopt::REQUIRED_ARGUMENT, "JSON file of configuration options."),
            array(null, 'density', Getopt::REQUIRED_ARGUMENT, "Source density for ImageMagick commands. Ex: \"--density=300\""),
            array(null, 'resize',  Getopt::REQUIRED_ARGUMENT, "Target dimensions. Ex: \"--resize=1920x1536\""),
            array('v',  'verbose', Getopt::NO_ARGUMENT,       "Verbose mode. Show extra message."),
            array(null, 'debug',   Getopt::NO_ARGUMENT,       "Show verbose and debug messages."),
            array(null, 'silent',  Getopt::NO_ARGUMENT,       "Do not output messages.")
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

        // Determine the verbosity.
        if ($getopt->getOption('debug') !== null) {
            $this->msg->setVerbosity(self::VERBOSITY_DEBUG);
        } elseif ($getopt->getOption('silent') !== null) {
            $this->msg->setVerbosity(self::VERBOSITY_SILENT);
        } elseif ($getopt->getOption('verbose') !== null) {
            $this->msg->setVerbosity(self::VERBOSITY_VERBOSE);
        }

        // Source
        if ($getopt->getOption('source') !== null) {
            $this->conf->set('source', $getopt->getOption('source'));
        }

        // Target
        if ($getopt->getOption('target') !== null) {
            $this->conf->set('target', $getopt->getOption('target'));
        }

        // Density
        if ($getopt->getOption('density') !== null) {
            $this->conf->set('density', $getopt->getOption('density'));
        }

        // Resize
        if ($getopt->getOption('resize') !== null) {
            $this->conf->set('resize', $getopt->getOption('resize'));
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

    private function readPdfInfo()
    {
        $source = $this->conf->get('source');

        if ($source === null) {
            throw new AppException("No source file provided. Please specify the path to a PDF with -i or --source.");
        }
        $source = realpath($source);
        if (!file_exists($source)) {
            throw new AppException("Source file '$source' not found.'");
        }

        $this->msg->write("Reading $source\n");
        $cmd = new ReadPdfInfoCommand($source, $this->conf);
        $this->msg->write($cmd->getCommandLine() . "\n", self::VERBOSITY_DEBUG);
        $cmd->run();
        $this->pdfInfo = $cmd->getInfo();
        $this->msg->write("Page Count: " . $this->pdfInfo->getPageCount() . "\n", self::VERBOSITY_DEBUG);
        $this->msg->write("Page Sizes:\n", self::VERBOSITY_DEBUG);
        foreach ($this->pdfInfo->getPageSizes() as $index => $size) {
            $this->msg->write("[$index] $size\n", self::VERBOSITY_DEBUG);
        }
        if ($this->pdfInfo->isUniform()) {
            $this->msg->write("All pages are the same dimensions.\n");
        } else {
            $this->msg->write("Page sizes are not uniform.\n");
        }
        $this->msg->write("Smallest Page Size: " . $this->pdfInfo->getSmallestPageSize() . "\n");
        $this->msg->write("Largest Page Size: " . $this->pdfInfo->getLargestPageSize() . "\n");
        if ($this->pdfInfo->containsSpreads()) {
            $this->msg->write("Spreads.\n");
        } else {
            $this->msg->write("No spreads.\n");
        }
    }

    private function outputPages()
    {
        $source = realpath($this->conf->get('source'));
        $target = $this->conf->get('target');
        if (!is_dir(realpath($target))) {
            if (!mkdir($target, null, true)) {
                throw new AppException("Unable to create directory $target");
            }
        }
        $target = realpath($target);
        $this->msg->write("Writing to $target\n", self::VERBOSITY_VERBOSE);

        $outputPagePattern = $this->conf->get('page-pattern', '%03d.jpg');
        $outoutPageIndex = 0;
        $inputPageSizes = $this->pdfInfo->getPageSizes();

        $smallest = $this->pdfInfo->getSmallestPageSize();

        for ($i = 0, $u = $this->pdfInfo->getPageCount(); $i < $u; $i++) {

            $sourcePage = $source . "[$i]";
            $inputPageSize = $inputPageSizes[$i];

            // Center vertical. Offset x will be determined later.
            $offset = new Point(0,0);
            if ($inputPageSize->height > $smallest->height) {
                $offset->y = ($inputPageSize->height - $smallest->height) / 2;
            }

            // Test if this input page is a spread containing two pages.
            if ($this->pdfInfo->isSpread($inputPageSize)) {

                // Spread

                // Left Page
                $targetPage = Util::joinPaths($target, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);
                $cmd->setCropSize($smallest);
                $offset->x = 0;
                $cmd->setCropOffset($offset);
                $this->msg->write($cmd->getCommandLine() . "\n");
                $cmd->run();
                $outoutPageIndex++;

                // Right Page
                $targetPage = Util::joinPaths($target, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);
                $cmd->setCropSize($smallest);
                $offset->x = $inputPageSize->width - $smallest->width;
                $cmd->setCropOffset($offset);
                $this->msg->write($cmd->getCommandLine() . "\n");
                $cmd->run();
                $outoutPageIndex++;

            } else {

                // Single Page

                $targetPage = Util::joinPaths($target, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);
                $cmd->setCropSize($smallest);

                // Center the cropped portion within the input page.
                if ($inputPageSize->width > $smallest->width) {
                    $offset->x = ($inputPageSize->width - $smallest->width) / 2;
                }
                $cmd->setCropOffset($offset);

                $this->msg->write($cmd->getCommandLine() . "\n");
                $cmd->run();
                $outoutPageIndex++;

            }
        }
    }
}
