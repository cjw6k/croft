<?php
/**
 * The WebFoo\Session class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Session class maintains session data for logged in users
 */
class Session
{

	use Aether;

	/**
	 * Construct the Session
	 *
	 * @param Config  $config  The active configuration.
	 * @param Request $request The current request.
	 *
	 * @throws Exception\Redirect A HTTP redirect is required.
	 */
	public function __construct(Config $config, Request $request)
	{
		$this->setConfig($config);
		$this->setRequest($request);

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
	 * Start the PHP session handler
	 *
	 * Updates a last access time so that active sessions are not garbage collected too soon.
	 *
	 * @return void
	 */
	private function _sessionStart()
	{
		if(PHP_SESSION_NONE == session_status()){
			session_start(
				array(
					'cookie_httponly' => true,
					'cookie_secure' => true,
				)
			);

			$this->getRequest()->session('last_access', now());
		}
	}

	/**
	 * Login a user
	 *
	 * @throws Exception\Redirect A HTTP redirect is required.
	 *
	 * @return boolean True  The login is successful.
	 *                 False The login is not successful.
	 */
	public function doLogin()
	{
		$request = $this->getRequest();

		if(!$request->post('username')){
			$this->mergeErrors('Username is required');
		}

		if(!$request->post('userkey')){
			$this->mergeErrors('Password is required');
		}

		if($this->hasErrors()){
			return false;
		}

		if(!password_verify($request->post('userkey'), $this->getConfig()->getPassword()) || $request->post('username') != $this->getConfig()->getUsername()){
			$this->mergeErrors('The username and password entered did not match the config. Please double-check and try again');
			return false;
		}

		$this->_sessionStart();
		$request->session('started', now());
		$request->session('logged_in', true);
		$request->session('ssrv', bin2hex(openssl_random_pseudo_bytes(16)));

		if($request->get('redirect_to')){
			$redirect_to = urldecode($request->get('redirect_to'));
			throw new Exception\Redirect('/' . $redirect_to);
		}

		throw new Exception\Redirect('/');
	}

	/**
	 * Logout a user
	 *
	 * @throws Exception\Redirect A HTTP redirect is required.
	 *
	 * @return void
	 */
	public function doLogout()
	{
		$this->getRequest()->session('logged_in', false);
		setcookie($this->_getCookieName(), '', -1, '/', '', true, true);
		throw new Exception\Redirect('/');
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
