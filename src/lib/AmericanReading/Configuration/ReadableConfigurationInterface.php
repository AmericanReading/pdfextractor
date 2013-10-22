<?php

namespace AmericanReading\Configuration;

interface ReadableConfigurationInterface
{
    public function get($setting, $default = null);
}
