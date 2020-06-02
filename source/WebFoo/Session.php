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
	 * @param Config $config The active configuration.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function __construct(Config $config)
	{
		$this->setConfig($config);

		session_name($this->_getCookieName());

		if(filter_input(INPUT_COOKIE, $this->_getCookieName())){
			$this->_sessionStart();

			if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']){
				$this->isLoggedIn(true);
				return;
			}

			// Session has expired, remove the old session cookie
			header('Location: /logout/');
		}
	}

	/**
	 * Start the PHP session handler
	 *
	 * Updates a last access time so that active sessions are not garbage collected too soon.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
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

			$_SESSION['last_access'] = now();
		}
	}

	/**
	 * Login a user
	 *
	 * @return boolean True  The login is successful.
	 *                 False The login is not successful.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function doLogin()
	{
		$username = filter_input(INPUT_POST, 'username');
		if(!isset($username) || empty($username)){
			$this->mergeErrors('Username is required');
		}

		$password = filter_input(INPUT_POST, 'userkey');
		if(!isset($password) || empty($password)){
			$this->mergeErrors('Password is required');
		}

		if($this->hasErrors()){
			return false;
		}

		if(!password_verify($password, $this->getConfig()->getPassword()) || $username != $this->getConfig()->getUsername()){
			$this->mergeErrors('The username and password entered did not match the config. Please double-check and try again');
			return false;
		}

		$this->_sessionStart();
		$_SESSION['started'] = now();
		$_SESSION['logged_in'] = true;

		if(isset($_GET['redirect_to'])){
			$redirect_to = urldecode(filter_input(INPUT_GET, 'redirect_to'));
			header('Location: /auth/?' . $redirect_to);
			return true;
		}

		header('Location: /');

		return true;
	}

	/**
	 * Logout a user
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function doLogout()
	{
		unset($_SESSION['logged_in']);
		setcookie($this->_getCookieName(), '', -1, '/', '', true, true);
		header('Location: /');
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
