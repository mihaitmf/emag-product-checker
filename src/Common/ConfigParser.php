<?php

namespace Notifier\Common;

use RuntimeException;

class ConfigParser
{
    private const CONFIG_FILE_NAME = 'config.ini';

    private array $configArray;

    public function __construct(array $configArray = [])
    {
        if ($configArray === []) {
            $configFilePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::CONFIG_FILE_NAME;
            $parsedConfig = parse_ini_file($configFilePath, true);
            if ($parsedConfig === false) {
                throw new RuntimeException(sprintf('Could not read config ini file from path: %s', $configFilePath));
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
    public function __get(string $name)
    {
        $value = $this->configArray[$name];
        if (is_array($value)) {
            return new ConfigParser($value);
        }

        return (string)$value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->configArray[$name]);
    }

    public function __set(string $name, string $value): void
    {
        throw new RuntimeException('Not allowed to set a config ini value dynamically');
    }
}
