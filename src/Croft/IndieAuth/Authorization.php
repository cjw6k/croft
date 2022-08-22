<?php

namespace Croft\IndieAuth;

use A6A\Aether\Aether;
use a6a\a6a\Config\Config;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\Response;
use Croft\From;

use function hash;
use function file_exists;
use function yaml_parse_file;
use function now;
use function yaml_emit_file;

/**
 * The Authorization class provides methods to service the authorization steps of IndieAuth
 */
class Authorization
{
    use Aether;

    /**
     * Store a local reference to required services
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
     * Handle an incoming authorization verification request
     *
     * @param Validation $validation Helper class for validating request parameters.
     *
     * @return bool True If the authorization code request is good.
 * False If the authorization code request is not good.
     */
    public function codeVerificationRequest(Validation $validation): bool
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if (! $validation->indieAuthRequestHasParams('authorization code verification')) {
            $this->setResponseBody($validation->getResponseBody());

            return false;
        }

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $code = $request->post('code');

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");

        if (! file_exists(From::VAR->dir() . 'indieauth/auth-' . $filename)) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the authorization code verification request could not be matched to an approved authentication response',
                ]
            );

            return false;
        }

        $approval = yaml_parse_file(From::VAR->dir() . 'indieauth/auth-' . $filename);

        if ((now() - 600) > $approval['expires']) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the authorization code verification request matched an approved authentication response that has already expired (10 mins)',
                ]
            );

            return false;
        }

        $this->setResponseBody(
            [
                'me' => $this->getConfig()->getMe(),
            ]
        );

        $approval['used']++;

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . 'indieauth/auth-' . $filename, $approval);

        if ($approval['used'] != 1) {
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the authorization code verification request matched an approved authentication response that has already been used',
                ]
            );

            return false;
        }

        return true;
    }
}
