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
	 * The Session instance
	 *
	 * @var WebFoo\Session
	 */
	private $_session;

	/**
	 * Construct the web stuff slinging thinger
	 *
	 * @param string $config_file The full path to the configuration file.
	 */
	public function __construct(string $config_file = PACKAGE_ROOT . 'config.yml')
	{
		$this->_config = new WebFoo\Config($config_file);
		$this->_session = new WebFoo\Session($this->_config);
	}

	/**
	 * Sling some web stuff with this thinger.
	 *
	 * @return void
	 */
	public function sling()
	{
		if('/login/' == filter_input(INPUT_SERVER, 'REQUEST_URI')){

			if('POST' == filter_input(INPUT_SERVER, 'REQUEST_METHOD')){
				if($this->getSession()->doLogin()){
					return;
				}
			}

			if(file_exists(TEMPLATES_LOCAL . 'login.php')){
				/**
				 * A file_exists check has succeeded at runtime.
				 *
				 * @psalm-suppress MissingFile
				 */
				include TEMPLATES_LOCAL . 'login.php';
				return;
			}

			include TEMPLATES_DEFAULT . 'login.php';
			return;
		}

		if('/logout/' == filter_input(INPUT_SERVER, 'REQUEST_URI')){
			$this->getSession()->doLogout();
			return;
		}

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

	/**
	 * Retrieve the user session
	 *
	 * @return WebFoo\Session The active session.
	 */
	public function getSession()
	{
		return $this->_session;
	}

	/**
	 * Setup an admin login
	 *
	 * @param array $argv The arguments provided on the comment line to setup.php.
	 *
	 * @return integer The exit status code.
	 */
	public function setup(array $argv) : int
	{
		if(file_exists(PACKAGE_ROOT . 'config.yml')){
			echo 'setup.php: first time setup is already complete.', PHP_EOL,
				 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return 1;
		}

		if(1 == count($argv)){
			echo 'Usage: setup.php [OPTIONS]... USERNAME', PHP_EOL,
				 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return 1;
		}

		$username = null;

		foreach($argv as $idx => $arg){
			if(0 == $idx){
				continue;
			}

			switch($arg){
				default:
					if(null != $username){
						echo 'Usage: setup.php [OPTIONS]... USERNAME', PHP_EOL,
							 'Try \'setup.php --help\' for more information.', PHP_EOL;
						return 1;
					}
					$username = $arg;
			}
		}

		if(empty($username)){
			echo 'Usage: setup.php [OPTIONS]... USERNAME', PHP_EOL,
				 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return 1;
		}

		$password = substr(base64_encode(random_bytes(12)), 0, 16);
		$password_hash = password_hash($password, PASSWORD_DEFAULT);

		$config = array(
			'username' => $username,
			'password' => $password_hash
		);

		if(!yaml_emit_file(PACKAGE_ROOT . 'config.yml', $config)){
			echo 'setup.php: An error occured writing the config to ' . PACKAGE_ROOT . 'config.yml.';
			return 1;
		}

		echo 'setup.php: Done! Enjoy WebFoo!', PHP_EOL, 'Your temporary password is: ', $password, PHP_EOL;
		return 0;
	}

	/**
	 * Output the webfoo controls HTML.
	 *
	 * @return void
	 */
	public function webfooControls()
	{
		if(!$this->getSession()->isLoggedIn()){
			return;
		}

		if(file_exists(TEMPLATES_LOCAL . 'webfoo_controls.php')){
			/**
			 * A file_exists check has succeeded at runtime.
			 *
			 * @psalm-suppress MissingFile
			 */
			include TEMPLATES_LOCAL . 'webfoo_controls.php';
			return;
		}

		include TEMPLATES_DEFAULT . 'webfoo_controls.php';
	}

}
