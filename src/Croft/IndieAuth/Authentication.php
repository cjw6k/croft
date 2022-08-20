<?php
/**
 * The IndieAuth\Authentication class is herein defined.
 *
 * @package WebFoo\IndieAuth
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\IndieAuth;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Exception\Redirect;
use \cjw6k\WebFoo\Request\RequestInterface;

/**
 * The Authentication class provides methods to service the authentication steps of IndieAuth
 */
class Authentication
{

    use Aether;

    /**
     * Store a local reference to required services
     *
     * @param RequestInterface $request The current request.
     */
    public function __construct(RequestInterface $request)
    {
        $this->setRequest($request);
    }

    /**
     * Handle an incoming authentication request
     *
     * @param Validation $validation Helper for validation of request parameters.
     *
     * @return void
     */
    public function handleRequest(Validation $validation)
    {
        $request = $this->getRequest();

        if('GET' == $request->getMethod()) {
            $this->_start($validation);
        }

        /**
         * The Aether trait provides full getNNN/setNNN implementations.
         *
         * This is the only place psalm finds the issue!
         *
         * @psalm-suppress PossiblyUndefinedMethod
         */
        if('POST' == $request->getMethod()) {
            if($request->post('ssrv') && $request->session('ssrv') == $request->post('ssrv')) {
                $this->_approve();
            }
        }
    }

    /**
     * Consider an incoming authentication request
     *
     * @param Validation $validation Helper for validation of request parameters.
     *
     * @return void
     */
    private function _start(Validation $validation)
    {
        $validation->authenticationRequest();

        if(!$validation->isValid()) {
            $this->setErrors($validation->getErrors());
        }
    }

    /**
     * Generate an authentication code and redirect to the client
     *
     * @throws Redirect A HTTP redirect is required.
     *
     * @return void
     */
    private function _approve()
    {
        $request = $this->getRequest();

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $state = $request->post('state');

        $code = str_replace(
            array('+', '/'),
            array('-', '_'),
            substr(
                bin2hex(openssl_random_pseudo_bytes(16)),
                0,
                20
            )
        );

        $approval = array(
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'code' => password_hash($code, PASSWORD_DEFAULT),
        'expires' => now() + 600,
        'used' => 0,
        'scopes' => $request->post('scopes'),
        );

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(VAR_ROOT . '/indieauth/auth-' . $filename, $approval);

        throw new Redirect($redirect_uri . '?code=' . $code . '&state=' . $state);
    }

}
