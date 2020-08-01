<?php
/**
 * The Session class is herein defined.
 *
 * @package	WebFoo\SessionFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\SessionFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Exception\Redirect;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Session\SessionInterface;

/**
 * The Session class maintains session data for logged in users
 */
class Session implements SessionInterface, Routable
{

	use Aether;

	/**
	 * Construct the Session
	 *
	 * @param ConfigInterface  $config  The active configuration.
	 * @param RequestInterface $request The current request.
	 */
	public function __construct(ConfigInterface $config, RequestInterface $request)
	{
		$this->setConfig($config);
		$this->setRequest($request);
	}

	/**
	 * Setup the session and start the session handler
	 *
	 * @return void
	 */
	public function start()
	{
		$request = $this->getRequest();

		session_name($this->_getCookieName());

		if($request->cookie($this->_getCookieName())){
			$this->_sessionStart();

			if($request->session('logged_in')){
				$this->isLoggedIn(true);
				$this->setSSRV($request->session('ssrv'));
				return;
			}

			// Session has expired, remove the old session cookie
			$this->doLogout();
		}
	}

	/**
	 * Provides a list of routes to register with the Router
	 *
	 * @return mixed|null The list of routes to register or null if there are none.
	 */
	public function getRoutes()
	{
		return array(
			new Route(array('GET', 'POST'), '/login/', 'doLogin'),
			new Route('GET', '/logout/', 'doLogout'),
		);
	}

	/**
	 * Start the PHP session handler
	 *
	 * Updates a last access time so that active sessions are not garbage collected too soon.
	 *
	 * @return void
	 */
	private function _sessionStart()
	{
		if(PHP_SESSION_NONE != session_status()){
			return;
		}

		session_start(
			array(
				'cookie_httponly' => true,
				'cookie_secure' => true,
			)
		);

		$this->getRequest()->session('last_access', now());
	}

	/**
	 * Login a user
	 *
	 * @throws Redirect A HTTP redirect is required.
	 *
	 * @return string The template to render.
	 */
	public function doLogin()
	{
		$request = $this->getRequest();

		if('POST' != $request->getMethod()){
			return 'login.php';
		}

		if(!$request->post('username')){
			$this->mergeErrors('Username is required');
		}

		if(!$request->post('userkey')){
			$this->mergeErrors('Password is required');
		}

		if($this->hasErrors()){
			return 'login.php';
		}

		if(!password_verify($request->post('userkey'), $this->getConfig()->getPassword()) || $request->post('username') != $this->getConfig()->getUsername()){
			$this->mergeErrors('The username and password entered did not match the config. Please double-check and try again');
			return 'login.php';
		}

		$this->_sessionStart();
		$request->session('started', now());
		$request->session('logged_in', true);
		$request->session('ssrv', bin2hex(openssl_random_pseudo_bytes(16)));

		if($request->get('redirect_to')){
			$redirect_to = urldecode($request->get('redirect_to'));
			throw new Redirect('/' . $redirect_to);
		}

		throw new Redirect('/');
	}

	/**
	 * Logout a user
	 *
	 * @throws Redirect A HTTP redirect is required.
	 *
	 * @return void
	 */
	public function doLogout()
	{
		$this->getRequest()->session('logged_in', false);
		setcookie($this->_getCookieName(), '', -1, '/', '', true, true);
		throw new Redirect('/');
	}

	/**
	 * Get the session cookie name from the config or use the default
	 *
	 * @return string The session cookie name.
	 */
	private function _getCookieName()
	{
		return $this->getConfig()->getCookieName() ? $this->getConfig()->getCookieName() : 'webfoo';
	}

}
