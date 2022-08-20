<?php

/**
 * The WebFoo\Config class is herein defined.
 *
 * @link https://cj.w6k.ca/
 */

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Config\ConfigInterface;
use Exception;

use function is_null;
use function file_exists;
use function yaml_parse_file;

/**
 * The Config class loads and saves configurations.
 */
class Config implements ConfigInterface
{
    use Aether;

    /**
     * Construct the Config
     *
     * @param string|null $config_file The full path to the configuration file.
     *
     * @throws Exception The specified config file does not exist.
     */
    public function __construct(?string $config_file = 'not-config.yml')
    {
        if (is_null($config_file)) {
            $this->_makeDefaultConfig();

            return;
        }

        if (file_exists($config_file)) {
            $config = yaml_parse_file($config_file);

            foreach ($config as $key => $value) {
                $this->setData($key, $value);
            }

            return;
        }

        // This should be a notice to run setup.php
        $this->_makeDefaultConfig();
    }

    /**
     * Setup a default configuration when no configuration file is available
     */
    private function _makeDefaultConfig(): void
    {
        $this->setTitle('the default config');
        $this->setMe('http://localhost/');
    }
}
