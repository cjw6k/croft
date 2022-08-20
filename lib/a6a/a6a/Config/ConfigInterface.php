<?php

namespace a6a\a6a\Config;

use a6a\a6a\Service\ServiceInterface;

/**
 * The Config service interface.
 */
interface ConfigInterface extends ServiceInterface
{
    /**
     * Construct the Config
     *
     * @param string|null $config_file The full path to the configuration file.
     */
    public function __construct(?string $config_file = null);
}
