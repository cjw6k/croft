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
	 * The IndieAuth instance
	 *
	 * @var WebFoo\IndieAuth
	 */
	private $_indieauth;

	/**
	 * The Request instance
	 *
	 * @var WebFoo\Request
	 */
	private $_request;

	/**
	 * Construct the web stuff slinging thinger
	 *
	 * @param string $config_file The full path to the configuration file.
	 */
	public function __construct(string $config_file = PACKAGE_ROOT . 'config.yml')
	{
		$this->_config = new WebFoo\Config($config_file);
		$this->_request = new WebFoo\Request();
		$this->_session = new WebFoo\Session($this->_config, $this->_request);
		$this->_indieauth = new WebFoo\IndieAuth();
	}

	/**
	 * Sling some web stuff with this thinger.
	 *
	 * @return void
	 */
	public function sling()
	{
		switch($this->getRequest()->getPath()){
			case '/auth/':
				$this->_slingAuth();
				break;

			case '/login/':
				$this->_slingLogin();
				break;

			case '/logout/':
				$this->getSession()->doLogout();
				return;

			case '/':
				$this->_includeTemplate('home.php', 'default.php');
				break;

			default:
				$this->_sling404();
				break;
		}
	}

	/**
	 * Control requests to /auth/
	 *
	 * @return void
	 */
	private function _slingAuth()
	{
		if(!$this->getSession()->isLoggedIn()){
			if(!empty($this->getRequest()->getQuery())){
				header('Location: /login/?redirect_to=' . trim($this->getRequest()->getPath(), '/') . '/?' . urlencode($this->getRequest()->getQuery()));
				return;
			}
			header('Location: /login/');
			return;
		}

		$this->getIndieAuth()->authenticationRequest($this->getRequest());

		if($this->getIndieAuth()->isValid()){
			$this->_includeTemplate('auth-good.php');
			return;
		}

		$this->_includeTemplate('auth-not_good.php');
	}

	/**
	 * Control requests to /login/
	 *
	 * @return void
	 */
	private function _slingLogin()
	{
		if('POST' == $this->getRequest()->getMethod()){
			if($this->getSession()->doLogin()){
				return;
			}
		}

		$this->_includeTemplate('login.php');
	}

	/**
	 * Control file not found requests
	 *
	 * @return void
	 */
	private function _sling404()
	{
		http_response_code(404);
		$this->getConfig()->setTitle(
			$this->getConfig()->getTitle() . ' - File Not Found'
		);

		$this->_includeTemplate('404.php', 'default.php');
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
	 * Retrieve the IndieAuth server
	 *
	 * @return WebFoo\IndieAuth The IndieAuth server.
	 */
	public function getIndieAuth()
	{
		return $this->_indieauth;
	}

	/**
	 * Retrieve the request
	 *
	 * @return WebFoo\Request The current request.
	 */
	public function getRequest()
	{
		return $this->_request;
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

		$this->_includeTemplate('webfoo_controls.php');
	}

	/**
	 * Send HTML to the client from a template file
	 *
	 * @param string $template  The filename to load.
	 * @param string $alternate The filename to load from default templates when the requested
	 *                          template is missing from the local templates.
	 *
	 * @return void
	 *
	 * @psalm-suppress UnresolvableInclude
	 */
	private function _includeTemplate(string $template, string $alternate = '')
	{
		if(file_exists(TEMPLATES_LOCAL . $template)){
			/**
			 * A file_exists check has succeeded at runtime.
			 *
			 * @psalm-suppress MissingFile
			 */
			include TEMPLATES_LOCAL . $template;
			return;
		}

		if(!empty($alternate)){
			include TEMPLATES_DEFAULT . $alternate;
			return;
		}

		include TEMPLATES_DEFAULT . $template;
	}

}
