<?php

namespace AmericanReading\Configuration;

use stdClass;

class Configuration implements ReadableConfigurationInterface
{
    /** @var Object containing the merged configuration data. */
    private $data;

    public function __construct($configuration = null)
    {
        $this->data = new stdClass();
        if ($configuration) {
            $this->load($configuration);
        }
    }

    /**
     * @param object $configuration Merge the passed configuration object with the current conf.
     */
    public function load($configuration)
    {
        $this->data = (object) array_merge((array) $this->data, (array) $configuration);
    }

    /**
     * @param string $setting Name of the setting to retrieve.
     * @param mixed $default Default value to return if the setting is not set.
     * @return mixed value for the setting or the default.
     */
    public function get($setting, $default=null)
    {
        if (isset($this->data->{$setting})) {
            return $this->data->{$setting};
        }
        return $default;
    }

    /**
     * Store a new value for a given setting.
     * @param string $setting
     * @param mixed $value
     */
    public function set($setting, $value)
    {
        $this->data->{$setting} = $value;
    }
}
