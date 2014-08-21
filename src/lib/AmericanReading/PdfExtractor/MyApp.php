<?php

namespace AmericanReading\PdfExtractor;

use AmericanReading\CliTools\App\App;
use AmericanReading\CliTools\App\AppException;
use AmericanReading\CliTools\Command\Command;
use AmericanReading\CliTools\Configuration\Configuration;
use AmericanReading\CliTools\Message\Messager;
use AmericanReading\CliTools\Message\MessagerInterface;
use AmericanReading\Geometry\Point;
use AmericanReading\Geometry\Size;
use AmericanReading\PdfExtractor\Command\BlankPageCommand;
use AmericanReading\PdfExtractor\Command\ConvertCommand;
use AmericanReading\PdfExtractor\Command\ReadPdfInfoCommand;
use AmericanReading\PdfExtractor\Components\GitHubRelease;
use AmericanReading\PdfExtractor\Data\PdfInfo;
use Phar;
use ProgressBar\Manager as ProgressBar;
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
    /** @var Size */
    private $normalizedPageSize;
    /** @var string */
    private $sourceFilePath;
    /** @var string */
    private $targetDirectoryPath;
    /** @var array Array of string commands to run with page numbers as keys. */
    private $commands;

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
        $timeStart = microtime(true);
        $this->readConfiguration();
        $this->readOptions($options);
        $this->msg->write("PDF Extractor\n", self::VERBOSITY_VERBOSE);
        if ($this->conf->get('clean', false)) {
            $this->clean();
        }
        $this->readPdfInfo();
        $this->buildOutputCommands();
        $this->runOutputCommands();
        $timeEnd = microtime(true);
        $this->msg->write(sprintf("Completed in %s seconds.\n", $timeEnd - $timeStart));
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
        $configurations = array();

        // Built-in configuration
        $configurations[] = Util::pharPath(self::CONFIGURATION_FILE_NAME);

        // User ~/.pdfextractor.json
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $configurations[] = Util::joinPaths(
                $_SERVER['HOMEDRIVE'],
                $_SERVER['HOMEPATH'],
                '.' . self::CONFIGURATION_FILE_NAME);
        } else {
            $configurations[] = Util::joinPaths(
                $_SERVER['HOME'],
                '.' . self::CONFIGURATION_FILE_NAME);
        }

        // Working directory: ./pdfextractor.json
        $configurations[] = Util::joinPaths(getcwd(), self::CONFIGURATION_FILE_NAME);

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
            array('h',  'help',       Getopt::NO_ARGUMENT,       "Show this help message."),
            array('v',  'verbose',    Getopt::NO_ARGUMENT,       "Verbose mode. Show extra message."),
            array('c',  'config',     Getopt::REQUIRED_ARGUMENT, "JSON file of configuration options."),
            array('i',  'source',     Getopt::REQUIRED_ARGUMENT, "Path to the PDF to process."),
            array('o',  'output',     Getopt::OPTIONAL_ARGUMENT, "Write image files to a directory with the same name as the source PDF base name. If an optional path is provded, this directory will be inside the provided path."),
            array('t',  'target',     Getopt::REQUIRED_ARGUMENT, "Write image files to a directory at the specified path. (Supercedes --output)"),
            array('p',  'pages',      Getopt::REQUIRED_ARGUMENT, "list of 1-based page number or ranges to export. Ex: \"--pages=1,4-6,10\""),
            array(null, 'blank',      Getopt::REQUIRED_ARGUMENT, "List of 1-based blank pages to insert. Ex: \"--blank=2,4\" Use negative numbers to indicate pages from the end."),
            array(null, 'skip',       Getopt::REQUIRED_ARGUMENT, "List of 0-based page indexes from the source PDF to skip. Ex: \"--skip-2,3\""),
            array(null, 'box',        Getopt::REQUIRED_ARGUMENT, "PDF box model. Allowed values: media, crop, trim. Ex: \"--box=crop\""),
            array(null, 'colorspace', Getopt::REQUIRED_ARGUMENT, "Convert to the specified colorspace. Ex: \"--colorspace=RGB\""),
            array(null, 'density',    Getopt::REQUIRED_ARGUMENT, "Source density for ImageMagick commands. Ex: \"--density=300\""),
            array(null, 'gutter',     Getopt::REQUIRED_ARGUMENT, "Pixels to ignore at the center of spreads. Ex: \"--gutter=75\""),
            array(null, 'normalize',  Getopt::REQUIRED_ARGUMENT, "Determine how to normalize the page size. May be [smallest], s, largest, l, or specific pixel dimensions (e.g., 3600x2400)."),
            array(null, 'quality',    Getopt::REQUIRED_ARGUMENT, "Target JPEG qualiy from 1-100 (100 least compressed) Ex: \"--quality=90\""),
            array(null, 'resize',     Getopt::REQUIRED_ARGUMENT, "Target dimensions. Ex: \"--resize=1920x1536\""),
            array(null, 'start',      Getopt::REQUIRED_ARGUMENT, "Set the numbering for the file names. Ex: \"--start=2\"."),
            array(null, 'valign',     Getopt::REQUIRED_ARGUMENT, "Determine how to extract pages when the normlized height it taller than the real height. Allowed values: top, [middle], bottom"),
            array(null, 'magick',     Getopt::REQUIRED_ARGUMENT, "Any extra parameted to pass through to the ImageMagick convert command."),
            array(null, 'concurrent', Getopt::REQUIRED_ARGUMENT, "Number of processes to run simultaneously. Ex: \"--concurrent=6\"."),
            array(null, 'clean',      Getopt::NO_ARGUMENT,       "Delete the contents of the target directory (before outputting)."),
            array(null, 'selfupdate', Getopt::NO_ARGUMENT,       "Update the utility."),
            array(null, 'debug',      Getopt::NO_ARGUMENT,       "Show verbose and debug messages."),
            array(null, 'silent',     Getopt::NO_ARGUMENT,       "Do not output messages."),
            array(null, 'version',    Getopt::NO_ARGUMENT,       "Display the version number.")
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

        // Version. Show version and exit.
        if ($getopt->getOption('version')) {
            $this->msg->write(sprintf("%s v%s\n%s\n", self::NAME, self::VERSION, self::COPYRIGHT));
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

        // Self update. Check for update and exit.
        if ($getopt->getOption('selfupdate')) {
            $this->selfUpdate();
            exit(0);
        }

        // Source
        if ($getopt->getOption('source') !== null) {
            $this->conf->set('source', $getopt->getOption('source'));
        }

        // Target
        if ($getopt->getOption('target') !== null) {
            $this->conf->set('target', $getopt->getOption('target'));
        } elseif ($getopt->getOption('output') !== null) {

            // Determine the name for the directory to output to based on the input file name.
            $source = $this->conf->get('source');
            $info = pathinfo($source);
            $directory = basename($source, '.' . $info['extension']);

            $output = $getopt->getOption('output');
            if (is_string($output)) {
                $directory = Util::joinPaths($output, $directory);
            }

            $this->conf->set('target', $directory);
        }

        // Clean
        if ($getopt->getOption('clean') !== null) {
            $this->conf->set('clean', true);
        }

        // Pages
        if ($getopt->getOption('pages') !== null) {
            $pages = array();
            $items = explode(',', $getopt->getOption('pages'));
            foreach ($items as $item) {
                if (is_numeric($item)) {
                    $pages[] = $item;
                } elseif (substr_count($item, '-') === 1) {
                    list($lower, $upper) = explode('-', $item);
                    $pages = array_merge($pages, range($lower, $upper));
                }
            }
            $pages = array_unique($pages, SORT_NUMERIC);
            $this->conf->set('pages', $pages);
        }

        // Skip
        if ($getopt->getOption('skip') !== null) {
            $pages = array();
            $items = explode(',', $getopt->getOption('skip'));
            foreach ($items as $item) {
                if (is_numeric($item)) {
                    $pages[] = $item;
                } elseif (substr_count($item, '-') === 1) {
                    list($lower, $upper) = explode('-', $item);
                    $pages = array_merge($pages, range($lower, $upper));
                }
            }
            $pages = array_unique($pages, SORT_NUMERIC);
            $this->conf->set('skip', $pages);
        }

        // Blank
        if ($getopt->getOption('blank') !== null) {
            $this->conf->set('blank', explode(',', $getopt->getOption('blank')));
        }

        // Box
        if ($getopt->getOption('box') !== null) {
            $this->conf->set('box', $getopt->getOption('box'));
        }

        // Colorspace
        if ($getopt->getOption('colorspace') !== null) {
            $this->conf->set('colorspace', $getopt->getOption('colorspace'));
        }

        // Density
        if ($getopt->getOption('density') !== null) {
            $this->conf->set('density', $getopt->getOption('density'));
        }

        // Normalize
        if ($getopt->getOption('normalize') !== null) {
            $this->conf->set('normalize', $getopt->getOption('normalize'));
        }

        // Gutter
        if ($getopt->getOption('gutter') !== null) {
            $this->conf->set('gutter', $getopt->getOption('gutter'));
        }

        // Quality
        if ($getopt->getOption('quality') !== null) {
            $this->conf->set('quality', $getopt->getOption('quality'));
        }

        // Resize
        if ($getopt->getOption('resize') !== null) {
            $this->conf->set('resize', $getopt->getOption('resize'));
        }

        // Vertical Alignment
        if ($getopt->getOption('valign') !== null) {
            $this->conf->set('valign', $getopt->getOption('valign'));
        }

        // Start
        if ($getopt->getOption('start') !== null) {
            $this->conf->set('start', $getopt->getOption('start'));
        }

        // Magick
        if ($getopt->getOption('magick') !== null) {
            $this->conf->set('magick', $getopt->getOption('magick'));
        }

        // Concurrent
        if ($getopt->getOption('concurrent') !== null) {
            $this->conf->set('concurrent', $getopt->getOption('concurrent'));
        }

        // Additional validation done after command line options are merged.

        // Ensure blanks contains unique integers.
        $blanks = $this->conf->get("blank");
        $blanks = array_map("intval", $blanks);
        $blanks = array_unique($blanks);
        $this->conf->set("blank", $blanks);
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
        $this->msg->write("Page Count: " . $this->pdfInfo->getPageCount() . "\n",  self::VERBOSITY_VERBOSE);
        $this->msg->write("Page Sizes:\n", self::VERBOSITY_VERBOSE);
        foreach ($this->pdfInfo->getPageSizes() as $index => $size) {
            $this->msg->write("[$index] $size\n", self::VERBOSITY_VERBOSE);
        }
        if ($this->pdfInfo->isUniform()) {
            $this->msg->write("All pages are the same dimensions.\n", self::VERBOSITY_VERBOSE);
        } else {
            $this->msg->write("Page sizes are not uniform.\n", self::VERBOSITY_VERBOSE);
        }
        $this->msg->write("Smallest Page Size: " . $this->pdfInfo->getSmallestPageSize() . "\n", self::VERBOSITY_VERBOSE);
        $this->msg->write("Largest Page Size: " . $this->pdfInfo->getLargestPageSize() . "\n", self::VERBOSITY_VERBOSE);
        if ($this->pdfInfo->containsSpreads()) {
            $this->msg->write("Spreads.\n", self::VERBOSITY_VERBOSE);
        } else {
            $this->msg->write("No spreads.\n", self::VERBOSITY_VERBOSE);
        }
    }

    private function buildOutputCommands()
    {
        // Build a list of commands. The array will be in the format pageNumber => command.
        $this->commands = array();

        // Find the full path to the input PDF.
        $this->sourceFilePath = realpath($this->conf->get('source'));

        // Find the full path to the output directory. Create the directory, if needed.
        $target = $this->conf->get('target');
        if (!is_dir(realpath($target))) {
            if (!mkdir($target, 0777, true)) {
                throw new AppException("Unable to create directory $target");
            }
        }
        $this->targetDirectoryPath = realpath($target);
        $this->msg->write("Writing to $target\n");
        unset($target);

        $outputPagePattern = $this->conf->get('page-pattern', '%03d.jpg');
        $outoutPageIndex = (int) $this->conf->get('start', 1);
        $sourcePageCount =  $this->pdfInfo->getPageCount();
        $inputPageSizes = $this->pdfInfo->getPageSizes();

        // Determine the smallest page size.
        $normalizeSize = $this->conf->get('normalize');
        switch ($normalizeSize) {
            case 'smallest':
            case 's':
                $this->normalizedPageSize = $this->pdfInfo->getSmallestPageSize();
                $this->msg->write(sprintf("Normlizing on smallest page size %s\n", $this->normalizedPageSize),
                    self::VERBOSITY_VERBOSE);
                break;
            case 'largest':
            case 'l':
                $this->normalizedPageSize = $this->pdfInfo->getLargestPageSize();
                $this->msg->write(sprintf("Normlizing on largest page size %s\n", $this->normalizedPageSize),
                    self::VERBOSITY_VERBOSE);
                break;
            default:
                if (is_string($normalizeSize)) {
                    $size = Size::initWithString($normalizeSize);
                    $this->msg->write(sprintf("Normlizing on specified page size %s\n", $this->normalizedPageSize),
                        self::VERBOSITY_VERBOSE);
                    $this->normalizedPageSize = $size;
                } else {
                    throw new AppException('Unable to parse "--normalize" option.');
                }
        }

        // If there is a gutter, recalculate the smallest page width.
        $gutter = $this->conf->get("gutter", 0);
        if ($gutter > 0) {
            if ($this->pdfInfo->containsSpreads()) {
                // For PDF with spreads, the gutter is only applied to spreads. Ensure that the
                // normalized page width can accomodate each spread, cut in half, with the gutter
                // removed.
                foreach ($inputPageSizes as $inputPagesize) {
                    if ($this->pdfInfo->isSpread($inputPagesize)) {
                        $singlePage = ($inputPagesize->width / 2) - $gutter;
                        $this->normalizedPageSize->width = min($this->normalizedPageSize->width, $singlePage);
                    }
                }
            } else {
                // For PDFs without spread, the gutter is removed from each page.
                $this->normalizedPageSize->width -= $gutter;
            }
        }

        // Read the source page indexes to skip.
        $skip =  $this->conf->get("skip", array());

        // Iterate over the source pages.
        for ($sourcePageIndex = 0; $sourcePageIndex < $sourcePageCount; $sourcePageIndex++) {

            if (in_array($sourcePageIndex, $skip)) {
                continue;
            }

            // For ImageMagick, the source page is the source file name with the index appended
            // inside of brackets, (e.g., mybook.pdf[0]).
            $sourcePage = $this->sourceFilePath . "[$sourcePageIndex]";

            $inputPageSize = $inputPageSizes[$sourcePageIndex];

            // Ensure the crop size fits within the image.
            // Reduce is with constrained proportions if it is too large.
            $normalSize = Size::initWithSize($this->normalizedPageSize);
            if ($normalSize->width > $inputPageSize->width) {
                $normalRatio = $normalSize->width / $normalSize->height;
                $normalSize->width = $inputPageSize->width;
                $normalSize->height = $inputPageSize->width / $normalRatio;
            }
            if ($normalSize->height > $inputPageSize->height) {
                $normalRatio = $normalSize->width / $normalSize->height;
                $normalSize->width = $inputPageSize->height * $normalRatio ;
                $normalSize->height = $inputPageSize->height;
            }

            // Determine the vertical offset.
            $offset = new Point(0,0);
            if ($inputPageSize->height > $normalSize->height) {
                switch ($this->conf->get('valign')) {
                    case 'top':
                        $offset->y = 0;
                        break;
                    case 'bottom':
                        $offset->y = $inputPageSize->height - $normalSize->height;
                        break;
                    case 'middle':
                        $offset->y = ($inputPageSize->height - $normalSize->height) / 2;
                        break;
                    default:
                        throw new AppException('Unable to parse value for option "valign". Allowed values are "top", "middle", and "bottom"');
                }
            }

            // Insert blank page.
            $this->buildBlankPageCommands($outoutPageIndex);

            // Test if this input page is a spread containing two pages.
            if ($this->pdfInfo->isSpread($inputPageSize)) {

                // Spread

                // Left Page
                $targetPage = Util::joinPaths($this->targetDirectoryPath, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);
                $cmd->setCropSize($normalSize);
                $offset->x = 0;
                $cmd->setCropOffset($offset);
                $this->msg->write($cmd->getCommandLine() . "\n", self::VERBOSITY_DEBUG);
                $this->commands[$outoutPageIndex] = $cmd->getCommandLine();
                $outoutPageIndex++;

                // Right Page
                $targetPage = Util::joinPaths($this->targetDirectoryPath, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);
                $cmd->setCropSize($normalSize);
                $offset->x = $inputPageSize->width - $normalSize->width;
                $cmd->setCropOffset($offset);
                $this->msg->write($cmd->getCommandLine() . "\n", self::VERBOSITY_DEBUG);
                $this->commands[$outoutPageIndex] = $cmd->getCommandLine();
                $outoutPageIndex++;

            } else {

                // Single Page
                $targetPage = Util::joinPaths($this->targetDirectoryPath, sprintf($outputPagePattern, $outoutPageIndex));
                $cmd = new ConvertCommand($sourcePage, $targetPage, $this->conf);

                if ($gutter > 0) {
                    // Shift the offset to remove the gutter.
                    $recto = ($outoutPageIndex % 2 === 1);
                    if ($recto) {
                        $offset->x = $gutter;
                    } else {
                        $offset->x = $inputPageSize->width - $normalSize->width - $gutter;
                    }
                } else {
                    // Center the cropped portion within the input page.
                    if ($inputPageSize->width > $normalSize->width) {
                        $offset->x = ($inputPageSize->width - $normalSize->width) / 2;
                    }
                }

                $cmd->setCropSize($normalSize);
                $cmd->setCropOffset($offset);
                $this->msg->write($cmd->getCommandLine() . "\n", self::VERBOSITY_DEBUG);
                $this->commands[$outoutPageIndex] = $cmd->getCommandLine();
                $outoutPageIndex++;

            }
        }

        // Remove commands if the user only wants to output select pages.
        $pagesToExport = $this->conf->get('pages');
        if (is_array($pagesToExport)) {
            foreach (array_keys($this->commands) as $pageNumber) {
                if (!in_array($pageNumber, $pagesToExport)) {
                    unset($this->commands[$pageNumber]);
                }
            }
        }

        // Check if the user wants to insert blank pages from the end of the document.
        // If so, convert the from end numbers (negatives) to from fron numbers (positive) and re-run this method.

        // Read the blanks pages.
        $blanks = $this->conf->get("blank", array());


        // Normalize the blanks.
        // This can't be done until after the first pass because the total number of pages is undefined until now.
        $updated = array_map(function ($page) use ($outoutPageIndex) {
                if ($page < 0) {
                    return $outoutPageIndex + $page;
                }
                return $page;
            }, $blanks);
        $updated = array_unique($updated);

        // Update the configuration and rerun, only if the configuration is different (there were pages from the end.)
        if (count($blanks) !== count($updated) || array_diff($blanks, $updated)) {
            $this->msg->write("Rebuilding with blank pages from the end.\n", self::VERBOSITY_VERBOSE);
            $this->conf->set("blank", $updated);
            $this->buildOutputCommands();
        }

    }

    private function buildBlankPageCommands(&$outoutPageIndex)
    {
        $blanks = $this->conf->get('blank', array());
        $outputPagePattern = $this->conf->get('page-pattern', '%03d.jpg');
        while (in_array($outoutPageIndex, $blanks)) {
            $targetPage = Util::joinPaths($this->targetDirectoryPath, sprintf($outputPagePattern, $outoutPageIndex));
            $cmd = new BlankPageCommand((string) $this->normalizedPageSize, $targetPage, $this->conf);
            $this->msg->write($cmd->getCommandLine() . "\n", self::VERBOSITY_DEBUG);
            $this->commands[$outoutPageIndex] =  $cmd->getCommandLine();
            $outoutPageIndex++;
        }
    }

    private function runOutputCommands()
    {
        $concurrent = $this->conf->get("concurrent", 1);
        if ($concurrent > 1 && function_exists('pcntl_fork')) {
            // Process control. Run in parallel.
            $this->runOutputCommandsParallel($concurrent);
        } else {
            // No process control. Run in series.
            $this->runOutputCommandsSerial();
        }
    }

    private function runOutputCommandsSerial()
    {
        // Create the progress bar.
        if ($this->msg->getVerbosity() >= self::VERBOSITY_SILENT) {
            $progress = 0;
            $progressBar = new ProgressBar(0, count($this->commands) - 1);
        }

        foreach ($this->commands as $cmdLine) {
            $cmd = new Command($cmdLine);
            $cmd->run();
            if (isset($progressBar, $progress)) {
                $progressBar->update($progress++);
            }
        }
    }

    private function runOutputCommandsParallel($concurrent)
    {
        // Build an array of runnable objects.
        $queued = array();
        foreach ($this->commands as $cmd) {
            $queued[] = new Command($cmd);
        }

        $running = array();
        $completed = array();

        // Create the progress bar.
        if ($this->msg->getVerbosity() >= self::VERBOSITY_SILENT) {
            $progress = 0;
            $progressBar = new ProgressBar(0, count($this->commands) - 1);
        }

        // Loop until the queue is emptied.
        while (count($completed) < count($this->commands)) {

            // Start processes until the queue is full.
            while (count($running) < min($concurrent, count($queued))) {

                // Pop the next command from the queue.
                // This must be done before forking.
                $cmd = array_pop($queued);
                $pid = pcntl_fork();

                if ($pid === -1) {
                    // Unable to fork.
                    $this->err->write("Unable to fork!");
                    exit(0);
                } elseif ($pid === 0) {
                    // Child.
                    $cmd->run();
                    exit(0);
                } else {
                    // Parent. Add this child's PID to running.
                    $running[] = $pid;
                }

            }

            // Wait for a process to finish.
            $pid = (pcntl_wait($status, WNOHANG OR WUNTRACED) > 0);

            if (($key = array_search($pid, $running)) !== false) {
                unset($running[$key]);
                $completed[] = $pid;
                if (isset($progressBar, $progress)) {
                    $progressBar->update($progress++);
                }
            }

            usleep(1000);

        }

    }

    /** Delete all files in the target directory. */
    private function clean()
    {
        $target = realpath($this->conf->get('target'));
        if (is_dir($target)) {
            $this->msg->write("Cleaning target directory\n");
            $files = glob(Util::joinPaths($target, '*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    $this->msg->write("Deleteing $file\n", self::VERBOSITY_VERBOSE);
                    unlink($file);
                }
            }
        }
    }

    private function selfUpdate()
    {
        $this->msg->write("Checking for updates...\n");
        $release = new GitHubRelease(self::GITHUB_REPOSITORY_OWNER, self::GITHUB_REPOSITORY_NAME);

        // Stop now if this is a current version of the app.
        if (version_compare($release->getVersion(), self::VERSION, '<=')) {
            $this->msg->write(self::NAME . " version " . self::VERSION  . " is already up to date.\n");
            exit(0);
        }

        $this->msg->write("Newer version " . $release->getVersion() . " found.\n");

        // Get a tmp file to download the phar to.
        $tempFile = tempnam(sys_get_temp_dir(), "phr");

        // Download the file.
        if (!$release->downloadAsset(self::GITHUB_ASSET_NAME, $tempFile)) {
            throw new AppException("Download failed.");
        }
        $this->msg->write("Download successful.\n");

        // Copty the downloaded file to replace the current app.
        if (!copy($tempFile, Phar::running(false))) {
            throw new AppException("Unable to replace existing file. Check your permissions.");
        }
        $this->msg->write("Update complete.\n");

        unlink($tempFile);
        exit(0);
    }
}
