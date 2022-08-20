<?php

namespace Croft\IndieAuth;

use A6A\Aether\Aether;
use a6a\a6a\Config\Config;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\Response;
use Croft\From;

use function json_encode;
use function hash;
use function file_exists;
use function yaml_parse_file;
use function now;
use function yaml_emit_file;
use function bin2hex;
use function openssl_random_pseudo_bytes;
use function implode;
use function str_replace;

/**
 * The Token class provides methods to service the tokens of IndieAuth
 */
class Token
{
    use Aether;

    /**
     * Store a local reference to the current request.
     *
     * @param Config $config The active configuration.
     * @param Request $request The current request.
     * @param Response $response The response.
     */
    public function __construct(Config $config, Request $request, Response $response)
    {
        $this->setConfig($config);
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Handle a request
     */
    public function handleRequest(): void
    {
        if ($this->getRequest()->post('action') == 'revoke') {
            $this->revocation();

            return;
        }

        if (! $this->request()) {
            $this->getResponse()->setCode(400);
        }

        echo json_encode($this->getResponseBody());
    }

    /**
     * Handle an incoming request
     *
     * @return bool True If the token request is good.
 * False If the token request is not good.
     */
    private function request(): bool
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if (! $this->hasRequiredParams(new Validation($this->getConfig(), $request))) {
            return false;
        }

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $code = $request->post('code');

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");

        if (! file_exists(From::VAR->dir() . 'indieauth/auth-' . $filename)) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token request could not be matched to an approved authorization response',
                ]
            );

            return false;
        }

        $approval = yaml_parse_file(From::VAR->dir() . 'indieauth/auth-' . $filename);

        if ((now() - 600) > $approval['expires']) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token request matched an approved authorization response that has already expired (10 mins)',
                ]
            );

            return false;
        }

        $approval['used']++;

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . 'indieauth/auth-' . $filename, $approval);

        if (! isset($approval['scopes'])) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token request matched an approved authentication response which authorizes no scopes',
                ]
            );

            return false;
        }

        if ($approval['used'] != 1) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token request matched an approved authorization response that has already been used',
                ]
            );

            return false;
        }

        $token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . 'indieauth/token-' . $token, ['auth' => $filename]);

        $response = $this->getResponse();
        $response->mergeHeaders('Cache-Control: no-store');
        $response->mergeHeaders('Pragma: no-cache');

        $this->setResponseBody(
            [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'scope' => implode(' ', $approval['scopes']),
                'me' => $this->getConfig()->getMe(),
            ]
        );

        return true;
    }

    /**
     * Check that a token request has required parameters
     *
     * @param Validation $validation Helper for validation of request parameters.
     *
     * @return bool True If the token request has required parameters.
 * False If the token request is missing required parameters.
     */
    private function hasRequiredParams(Validation $validation): bool
    {
        $request = $this->getRequest();

        if (! $request->post('grant_type')) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the token request was missing the grant_type parameter',
                ]
            );

            return false;
        }

        if ($request->post('grant_type') != 'authorization_code') {
            $this->setResponseBody(
                [
                    'error' => 'unsupported_grant_type',
                    'error_description' => 'the requested grant type is not supported here',
                ]
            );

            return false;
        }

        if (! $request->post('me')) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the token request was missing the user profile URL (me) parameter',
                ]
            );

            return false;
        }

        if (! $validation->indieAuthRequestHasParams('token')) {
            $this->setResponseBody($validation->getResponseBody());

            return false;
        }

        return true;
    }

    /**
     * Revoke an access token if it exists
     */
    private function revocation(): void
    {
        $token = $this->getRequest()->post('token');

        if (! $token) {
            return;
        }

        if (! file_exists(From::VAR->dir() . 'indieauth/token-' . $token)) {
            return;
        }

        $access_token = yaml_parse_file(From::VAR->dir() . 'indieauth/token-' . $token);

        if (! $access_token) {
            return;
        }

        $access_token['revoked'] = now();

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . 'indieauth/token-' . $token, $access_token);
    }

    /**
     * Provide authorization details in response to requests with a valid bearer token
     */
    public function handleVerificationRequest(): void
    {
        $response = $this->getResponse();
        $response->mergeHeaders('Cache-Control: no-store');
        $response->mergeHeaders('Pragma: no-cache');
        $response->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        $auth_header = $this->getRequest()->server('HTTP_AUTHORIZATION');

        if (! $auth_header) {
            $response->setCode(400);
            echo json_encode(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the token verification request did not provided a bearer token',
                ]
            );

            return;
        }

        if (! $this->verifyToken(str_replace('Bearer ', '', $auth_header))) {
            return;
        }

        echo json_encode(
            [
                'me' => $this->getConfig()->getMe(),
                'client_id' => $this->getClientId(),
                'scope' => implode(' ', $this->getScopes()),
            ]
        );
    }

    /**
     * Verify that the supplied token matches a token issued here
     *
     * @param string $token The supplied access token.
     *
     * @return bool True If the access token is valid here.
 * False If the access token is not valid here.
     */
    private function verifyToken(string $token): bool
    {
        if (! file_exists(From::VAR->dir() . 'indieauth/token-' . $token)) {
            $this->getResponse()->setCode(401);
            echo json_encode(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token verification request could not be matched to an issued token',
                ]
            );

            return false;
        }

        $token_record = yaml_parse_file(From::VAR->dir() . 'indieauth/token-' . $token);

        if (! $token_record) {
            $this->getResponse()->setCode(500);

            return false;
        }

        if (isset($token_record['revoked'])) {
            $this->getResponse()->setCode(403);
            echo json_encode(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token verification request included a bearer token which has been revoked',
                ]
            );

            return false;
        }

        $auth = yaml_parse_file(From::VAR->dir() . 'indieauth/auth-' . $token_record['auth']);

        if (! $auth) {
            $this->getResponse()->setCode(500);

            return false;
        }

        if ($auth['used'] != 1) {
            $this->getResponse()->setCode(403);
            echo json_encode(
                [
                    'error' => 'invalid_grant',
                    'error_description' => 'the token verification request included a bearer token which originates from a cancelled authorization',
                ]
            );

            return false;
        }

        $this->setScopes($auth['scopes']);
        $this->setClientId($auth['client_id']);

        return true;
    }
}
