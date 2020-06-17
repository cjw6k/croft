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

	use WebFoo\Aether;

	/**
	 * Construct the web stuff slinging thinger
	 *
	 * @param string $config_file The full path to the configuration file.
	 */
	public function __construct(string $config_file = PACKAGE_ROOT . 'config.yml')
	{
		$this->setConfig(new WebFoo\Config($config_file));
		$this->setRequest(new WebFoo\Request());
		$this->setIndieAuth(new WebFoo\IndieAuth($this->getConfig()));
		$this->setMicropub(new WebFoo\Micropub($this->getConfig()));

		$links = array_merge(
			$this->getIndieAuth()->getHTTPLinks(),
			$this->getMicropub()->getHTTPLinks()
		);
		if($links){
			header('Link: ' . implode(',', $links));
		}
	}

	/**
	 * Sling some web stuff with this thinger.
	 *
	 * @return void
	 */
	public function sling()
	{
		try {
			$this->setSession(new WebFoo\Session($this->getConfig(), $this->getRequest()));

			switch($this->getRequest()->getPath()){
				case '/auth/':
					$this->_slingAuth();
					break;

				case '/token/':
					$this->_slingToken();
					break;

				case '/micropub/':
					$this->_slingMicropub();
					break;

				case '/login/':
					$this->_slingLogin();
					break;

				case '/logout/':
					$this->getSession()->doLogout();
					break;

				case '/':
					$this->_includeTemplate('home.php', 'default.php');
					break;

				default:
					if(!$this->_slingContent()){
						$this->_sling404();
					}
					break;
			}
		} catch (WebFoo\Exception\Redirect $redirect){
			header('Location: ' . $redirect->getMessage());
			return;
		}
	}

	/**
	 * Control requests to /auth/
	 *
	 * @throws WebFoo\Exception\Redirect A HTTP redirect is required.
	 *
	 * @return void
	 */
	private function _slingAuth()
	{
		if(!$this->getSession()->isLoggedIn()){
			if('POST' == $this->getRequest()->getMethod()){
				if(!$this->getIndieAuth()->authorizationCodeVerificationRequest($this->getRequest())){
					http_response_code(400);
				}
				echo json_encode($this->getIndieAuth()->getResponse());
				return;
			}

			if(!empty($this->getRequest()->getQuery())){
				throw new WebFoo\Exception\Redirect('/login/?redirect_to=' . trim($this->getRequest()->getPath(), '/') . '/?' . urlencode($this->getRequest()->getQuery()));
			}
			throw new WebFoo\Exception\Redirect('/login/?redirect_to=' . trim($this->getRequest()->getPath(), '/') . '/?' . urlencode($this->getRequest()->getQuery()));
		}

		$this->getIndieAuth()->authenticationRequest($this->getRequest());

		if($this->getIndieAuth()->isValid()){
			$this->_includeTemplate('auth-good.php');
			return;
		}

		$this->_includeTemplate('auth-not_good.php');
	}

	/**
	 * Control requests to /token/
	 *
	 * @return void
	 */
	private function _slingToken()
	{
		if('POST' == $this->getRequest()->getMethod()){
			if('revoke' == $this->getRequest()->post('action')){
				$this->getIndieAuth()->tokenRevocation($this->getRequest());
				return;
			}

			if(!$this->getIndieAuth()->tokenRequest($this->getRequest())){
				http_response_code(400);
			}
			echo json_encode($this->getIndieAuth()->getResponse());
			return;
		}

		$this->_sling404();
	}

	/**
	 * Control requests to /micropub/
	 *
	 * @return void
	 */
	private function _slingMicropub()
	{
		$this->getMicropub()->handleRequest($this->getRequest());
		if($this->getMicropub()->hasResponse()){
			echo json_encode($this->getMicropub()->getResponse());
		}
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
	 * Control contents requests
	 *
	 * @return boolean True  If the request matches content.
	 *                 False If the request does not match any content.
	 */
	private function _slingContent()
	{
		$matches = array();
		if(!preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)/', $this->getRequest()->getPath(), $matches)){
			return false;
		}

		$this->setContent(file_get_contents(CONTENT_ROOT . $matches[1] . 'web.foo'));
		$this->_includeTemplate('content.php');

		return true;
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

		if(3 > count($argv)){
			echo 'Usage: setup.php [OPTIONS]... USERNAME URL', PHP_EOL,
				 'Try \'setup.php --help\' for more information.', PHP_EOL;
			return 1;
		}

		$setup = new WebFoo\Setup($this->getIndieAuth(), $this->getRequest());
		return $setup->configure($argv);
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
