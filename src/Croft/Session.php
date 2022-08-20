<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Session\SessionInterface;

use function session_name;

use const PHP_SESSION_NONE;

use function session_status;
use function session_start;
use function now;
use function password_verify;
use function bin2hex;
use function openssl_random_pseudo_bytes;
use function urldecode;
use function setcookie;

/**
 * The Session class maintains session data for logged in users
 */
class Session implements SessionInterface, Routable
{
    use Aether;

    /**
     * Construct the Session
     *
     * @param ConfigInterface $config The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request)
    {
        $this->setConfig($config);
        $this->setRequest($request);
    }

    /**
     * Setup the session and start the session handler
     */
    public function start(): void
    {
        $request = $this->getRequest();

        session_name($this->_getCookieName());

        if (! $request->cookie($this->_getCookieName())) {
            return;
        }

        $this->_sessionStart();

        if ($request->session('logged_in')) {
            $this->isLoggedIn(true);
            $this->setSSRV($request->session('ssrv'));

            return;
        }

        // Session has expired, remove the old session cookie
        $this->doLogout();
    }

    /**
     * Provides a list of routes to register with the Router
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed
    {
        return [
            new Route(['GET', 'POST'], '/login/', 'doLogin'),
            new Route('GET', '/logout/', 'doLogout'),
        ];
    }

    /**
     * Start the PHP session handler
     *
     * Updates a last access time so that active sessions are not garbage collected too soon.
     */
    private function _sessionStart(): void
    {
        if (session_status() != PHP_SESSION_NONE) {
            return;
        }

        session_start(['cookie_httponly' => true, 'cookie_secure' => true]);

        $this->getRequest()->session('last_access', now());
    }

    /**
     * Login a user
     *
     * @return string The template to render.
     *
     * @throws Redirect A HTTP redirect is required.
     */
    public function doLogin(): string
    {
        $request = $this->getRequest();

        if ($request->getMethod() != 'POST') {
            return 'login.php';
        }

        if (! $request->post('username')) {
            $this->mergeErrors('Username is required');
        }

        if (! $request->post('userkey')) {
            $this->mergeErrors('Password is required');
        }

        if ($this->hasErrors()) {
            return 'login.php';
        }

        if (
            ! password_verify($request->post('userkey'), $this->getConfig()->getPassword())
            || $request->post('username') != $this->getConfig()->getUsername()
        ) {
            $this->mergeErrors('The username and password entered did not match the config. Please double-check and try again');

            return 'login.php';
        }

        $this->_sessionStart();
        $request->session('started', now());
        $request->session('logged_in', true);
        $request->session('ssrv', bin2hex(openssl_random_pseudo_bytes(16)));

        if ($request->get('redirect_to')) {
            $redirect_to = urldecode($request->get('redirect_to'));
            throw new Redirect('/' . $redirect_to);
        }

        throw new Redirect('/');
    }

    /**
     * Logout a user
     *
     * @throws Redirect A HTTP redirect is required.
     */
    public function doLogout(): void
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
    private function _getCookieName(): string
    {
        return $this->getConfig()->getCookieName()
            ?: 'webfoo';
    }
}
