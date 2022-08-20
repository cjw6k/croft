<?php

namespace Croft\IndieAuth;

use A6A\Aether\Aether;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Request\RequestInterface;
use Croft\From;

use function str_replace;
use function substr;
use function bin2hex;
use function openssl_random_pseudo_bytes;
use function password_hash;

use const PASSWORD_DEFAULT;

use function now;
use function hash;
use function yaml_emit_file;

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
     */
    public function handleRequest(Validation $validation): void
    {
        $request = $this->getRequest();

        if ($request->getMethod() == 'GET') {
            $this->_start($validation);
        }

        /**
         * The Aether trait provides full getNNN/setNNN implementations.
         *
         * This is the only place psalm finds the issue!
         *
         * @psalm-suppress PossiblyUndefinedMethod
         */
        if ($request->getMethod() != 'POST') {
            return;
        }

        if (
            ! $request->post('ssrv')
            || $request->session('ssrv') != $request->post('ssrv')
        ) {
            return;
        }

        $this->_approve();
    }

    /**
     * Consider an incoming authentication request
     *
     * @param Validation $validation Helper for validation of request parameters.
     */
    private function _start(Validation $validation): void
    {
        $validation->authenticationRequest();

        if ($validation->isValid()) {
            return;
        }

        $this->setErrors($validation->getErrors());
    }

    /**
     * Generate an authentication code and redirect to the client
     *
     * @throws Redirect A HTTP redirect is required.
     */
    private function _approve(): void
    {
        $request = $this->getRequest();

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $state = $request->post('state');

        $code = str_replace(
            ['+', '/'],
            ['-', '_'],
            substr(
                bin2hex(openssl_random_pseudo_bytes(16)),
                0,
                20
            )
        );

        $approval = [
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'expires' => now() + 600,
            'used' => 0,
            'scopes' => $request->post('scopes'),
        ];

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . '/indieauth/auth-' . $filename, $approval);

        throw new Redirect($redirect_uri . '?code=' . $code . '&state=' . $state);
    }
}
