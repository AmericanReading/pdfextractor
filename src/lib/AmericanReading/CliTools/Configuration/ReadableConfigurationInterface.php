<?php

namespace AmericanReading\CliTools\Configuration;

interface ReadableConfigurationInterface
{
    public function get($setting, $default = null);
}
