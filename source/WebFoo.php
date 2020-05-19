<?php
/**
 * The WebFoo class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k;

use \Exception;

/**
 * The WebFoo Class is the main slingin' thinger.
 */
class WebFoo
{

	/**
	 * The global configuration
	 *
	 * @var WebFoo\Config
	 */
	private $_config;

	/**
	 * Construct the web stuff slinging thinger
	 *
	 * @param string|null $config_file The full path to the configuration file.
	 */
	public function __construct(string $config_file = null)
	{
		$this->_config = new WebFoo\Config($config_file);
	}

	/**
	 * Sling some web stuff with this thinger.
	 *
	 * @return void
	 */
	public function sling()
	{
		if('/' != filter_input(INPUT_SERVER, 'REQUEST_URI')){
			http_response_code(404);
			$this->getConfig()->setTitle(
				$this->getConfig()->getTitle() . ' - File Not Found'
			);

			if(file_exists(TEMPLATES_LOCAL . '404.php')){
				/**
				 * A file_exists check has succeeded at runtime.
				 *
				 * @psalm-suppress MissingFile
				 */
				include TEMPLATES_LOCAL . '404.php';
				return;
			}

			include TEMPLATES_DEFAULT . 'default.php';
			return;
		}

		if(file_exists(TEMPLATES_LOCAL . 'home.php')){
			/**
			 * A file_exists check has succeeded at runtime.
			 *
			 * @psalm-suppress MissingFile
			 */
			include TEMPLATES_LOCAL . 'home.php';
			return;
		}

		include TEMPLATES_DEFAULT . 'default.php';
	}

	/**
	 * Retrieve the global config
	 *
	 * @return WebFoo\Config The active configuration.
	 */
	public function getConfig()
	{
		return $this->_config;
	}

}
