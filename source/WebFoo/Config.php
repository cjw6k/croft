<?php
/**
 * The WebFoo\Config class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

use \Exception;

/**
 * The Config class loads and saves configurations.
 */
class Config
{

	use Aether;

	/**
	 * Construct the Config
	 *
	 * @param string|null $config_file The full path to the configuration file.
	 *
	 * @throws Exception The specified config file does not exist.
	 */
	public function __construct($config_file = null)
	{
		if(is_null($config_file)){
			$this->_makeDefaultConfig();
			return;
		}

		if(file_exists($config_file)){
			$config = \yaml_parse_file($config_file);
			foreach($config as $key => $value){
				$this->setData($key, $value);
			}
			return;
		}

		// This should be a notice to run setup.php
		$this->_makeDefaultConfig();
	}

	/**
	 * Setup a default configuration when no configuration file is available
	 *
	 * @return void
	 */
	private function _makeDefaultConfig()
	{
		$this->setTitle('WebFoo');
		$this->setMe('http://localhost/');
	}

}
