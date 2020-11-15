<?php

namespace Notifier\Common;

use RuntimeException;

class ConfigParser
{
    const CONFIG_FILE_PATH = '../config.ini';

    /** @var array */
    private $configArray;

    public function __construct(array $configArray = [])
    {
        if ($configArray === []) {
            $parsedConfig = parse_ini_file(self::CONFIG_FILE_PATH, true);
            if ($parsedConfig === false) {
                throw new \RuntimeException(sprintf('Could not read config ini file from path: %s', self::CONFIG_FILE_PATH));
            }
            $this->configArray = $parsedConfig;
        } else {
            $this->configArray = $configArray;
        }
    }

    /**
     * @param string $name
     *
     * @return ConfigParser|string
     */
    public function __get($name)
    {
        $value = $this->configArray[$name];
        if (is_array($value)) {
            return new ConfigParser($value);
        }

        return (string)$value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->configArray[$name]);
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        throw new RuntimeException('Not allowed to set a config ini value dynamically');
    }
}
