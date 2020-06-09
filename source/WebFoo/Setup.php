<?php
/**
 * The Setup class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Setup completes first-time configuratrion of WebFoo
 *
 * Setup is triggered by running the setup.php script in the bin folder of the package root.
 *
 * setup.php requires USERNAME and URL parameters corresponding to the login username and the
 * IndieAuth user profile URL. A password is generated and displayed on the command line when
 * setup completes successfully.
 */
class Setup
{

	use Aether;

	/**
	 * Construct the Setup
	 *
	 * @param IndieAuth $indieauth The IndieAuth implementation.
	 * @param Request   $request   The current request.
	 */
	public function __construct(IndieAuth $indieauth, Request $request)
	{
		$this->setIndieAuth($indieauth);
		$this->setRequest($request);
	}

	/**
	 * Configure WebFoo using parameters provided on the command line
	 *
	 * @param mixed $argv The command lines parameters.
	 *
	 * @return integer The return code of the setup.php script.
	 */
	public function configure($argv)
	{
		if(!$this->_hasRequiredParameters($argv)){
			return 1;
		}

		$this->_ensureURLHasPath();

		if(!$this->_hasValidURL()){
			return 1;
		}

		$password = substr(base64_encode(random_bytes(12)), 0, 16);
		$password_hash = password_hash($password, PASSWORD_DEFAULT);

		$config = array(
			'username' => $this->getUsername(),
			'password' => $password_hash,
			'me' => $this->getUrl(),
		);

		if(!yaml_emit_file(PACKAGE_ROOT . 'config.yml', $config)){
			echo 'setup.php: An error occured writing the config to ' . PACKAGE_ROOT . 'config.yml.';
			return 1;
		}

		echo 'setup.php: Done! Enjoy WebFoo!', PHP_EOL, 'Your temporary password is: ', $password, PHP_EOL;
		return 0;
	}

	/**
	 * Check that the setup script was called with all required parameters
	 *
	 * @param mixed $argv The command lines parameters.
	 *
	 * @return boolean True  If called with all required parameters.
	 *                 False If not called with all required parameters.
	 */
	private function _hasRequiredParameters($argv)
	{
		foreach($argv as $idx => $arg){
			if(0 == $idx){
				continue;
			}

			if(!$this->_parseParameter($arg)){
				return false;
			}
		}

		if(empty($this->getUsername()) || empty($this->getUrl())){
			echo 'Usage: setup.php [OPTIONS]... USERNAME URL', PHP_EOL,
				 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return false;
		}

		return true;
	}

	/**
	 * Parse one command line parameter
	 *
	 * @param string $param The command line parameter to parse.
	 *
	 * @return boolean True  If parsed okay.
	 *                 False If not parsed okay.
	 */
	private function _parseParameter(string $param)
	{
		if(!$this->hasUsername()){
			$this->setUsername($param);
			return true;
		}

		if(!$this->hasUrl()){
			$this->setUrl($param);
			return true;
		}

		echo 'Usage: setup.php [OPTIONS]... USERNAME URL', PHP_EOL,
			 'Try \'setup.php --help\' for more information.', PHP_EOL;

		return false;
	}

	/**
	 * Append the root path component '/' if missing from the URL
	 *
	 * @return void
	 */
	private function _ensureURLHasPath()
	{
		$url_parts = parse_url($this->getUrl());
		if(!isset($url_parts['path'])){
			$this->setUrl($this->getUrl() . '/');
		}
	}

	/**
	 * Check that the supplied URL is a valid user profile URL according to the IndieAuth spec
	 *
	 * @return boolean True  If the URL is valid.
	 *                 False If the URL is not valid.
	 */
	private function _hasValidURL()
	{
		if(!$this->getIndieAuth()->validateUserProfileURL($this->getUrl(), $this->getRequest())){
			foreach($this->getIndieAuth()->getErrors() as $error){
				echo 'error: ', $error, PHP_EOL;
			}
			echo 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return false;
		}

		return true;
	}

}
