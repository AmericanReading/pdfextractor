<?php

namespace AmericanReading\PdfExtractor\Command;

use AmericanReading\CliTools\Command\CommandBase;
use AmericanReading\CliTools\Configuration\ReadableConfigurationInterface;
use AmericanReading\PdfExtractor\ConfigInterface;

abstract class ConfiguredCommand extends CommandBase implements ConfigInterface
{
    /** @var ReadableConfigurationInterface */
    protected $configuration;

    public function __construct(ReadableConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }
}
