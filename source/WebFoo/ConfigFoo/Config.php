<?php
/**
 * The WebFoo\Config class is herein defined.
 *
 * @package	WebFoo\ConfigFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\ConfigFoo;

use \Exception;
use yaml_parse_file;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;

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
	public function __construct($config_file = PACKAGE_ROOT . 'config.yml')
	{
		if(is_null($config_file)){
			$this->_makeDefaultConfig();
			return;
		}

		if(file_exists($config_file)){
			$config = yaml_parse_file($config_file);
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
