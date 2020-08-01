<?php
/**
 * The ConfigInterface interface is herein defined.
 *
 * @package	WebFoo\Config
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Config;

use \cjw6k\WebFoo\Service\ServiceInterface;

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
	public function __construct($config_file = PACKAGE_ROOT . 'config.yml');

}
