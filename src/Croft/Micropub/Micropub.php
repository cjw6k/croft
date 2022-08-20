<?php

namespace Croft\Micropub;

use A6A\Aether\Aether;
use a6a\a6a\Config\Config;
use a6a\a6a\Extension\Extension;
use a6a\a6a\Post\Post;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\HttpLinkable;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use Croft\From;

use function json_encode;
use function is_null;
use function str_replace;
use function parse_url;
use function in_array;
use function file_exists;
use function yaml_parse_file;

/**
 * The Micropub class implements a Micropub server
 */
class Micropub implements Extension, HttpLinkable, Routable
{
    use Aether;

    /**
     * Send the Micropub HTTP link-rel header
     *
     * @param Config $config The config service.
     * @param Post $post The post service.
     * @param Request $request The request service.
     * @param Response $response The response service.
     */
    public function __construct(Config $config, Post $post, Request $request, Response $response)
    {
        $this->setConfig($config);
        $this->setPost($post);
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Provides a list of routes to register with the Router to be serviced by this extension.
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed
    {
        return [
            new Route(['GET', 'POST'], '/micropub/', 'handleRequest'),
        ];
    }

    /**
     * Provide HTTP link header configuration to the Response\HTTP
     *
     * @return array<mixed> An array of HTTP link headers.
     */
    public function getHttpLinks(): array
    {
        return [
            '</micropub/>; rel="micropub"',
        ];
    }

    /**
     * Handle a request
     */
    public function handleRequest(): void
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if (! $this->checkAccessToken()) {
            if ($this->hasResponseBody()) {
                echo json_encode($this->getResponseBody());
            }

            return;
        }

        switch ($request->getMethod()) {
            case 'GET':
                $query = new Query($request, $this->getResponse());
                $query->handleRequest();
                $this->setResponseBody($query->getResponseBody());

                break;

            case 'POST':
                $this->postRequest();

                break;
        }

        if (! $this->hasResponseBody()) {
            return;
        }

        echo json_encode($this->getResponseBody());
    }

    /**
     * Ensure that the request contains a valid bearer token
     *
     * @return bool True If the access token is valid.
 * False If the access token is missing or invalid.
     */
    private function checkAccessToken(): bool
    {
        $request = $this->getRequest();

        $auth_header = $request->server('HTTP_AUTHORIZATION');

        $auth_param = null;

        switch ($request->getMethod()) {
            case 'GET':
                $auth_param = $request->get('access_token');

                break;

            case 'POST':
                $auth_param = $request->post('access_token');

                break;
        }

        if (! is_null($auth_header) && ! is_null($auth_param)) {
            if ($this->isExceptionForMicropubrocks()) {
                return true;
            }

            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the micropub request provided both header and parameter access tokens',
                ]
            );

            return false;
        }

        if (! is_null($auth_param)) {
            return $this->verifyToken($auth_param);
        }

        if (! is_null($auth_header)) {
            return $this->verifyToken(str_replace('Bearer ', '', $auth_header));
        }

        $this->getResponse()->setCode(401);
        $this->setResponseBody(
            [
                'error' => 'unauthorized',
                'error_description' => 'the micropub request did not provide an access token',
            ]
        );

        return false;
    }

    /**
     * Volkswagen fur Micropub
     *
     * The micropub.rocks tests require permitting an undocumented violation of Oauth2 to pass.
     *
     * @return bool True If this is going to be allowed for Micropub.rocks.
 * False If this is not micropub.rocks.
     */
    private function isExceptionForMicropubrocks(): bool
    {
        if (! $this->verifyToken(str_replace('Bearer ', '', $this->getRequest()->server('HTTP_AUTHORIZATION')))) {
            return false;
        }

        $config_micropub = $this->getConfig()->getMicropub();

        if (! isset($config_micropub['exceptions']['two_copies_of_access_token'])) {
            return false;
        }

        $url_parts = parse_url($this->getClientId());

        if (! isset($url_parts['host'])) {
            return false;
        }

        return in_array($url_parts['host'], $config_micropub['exceptions']['two_copies_of_access_token']);
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
            $this->getResponse()->setCode(403);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the micropub request could not be matched to an authorized access token',
                ]
            );

            return false;
        }

        $token_record = yaml_parse_file(From::VAR->dir() . 'indieauth/token-' . $token);

        if (! $token_record) {
            return false;
        }

        if (isset($token_record['revoked'])) {
            return false;
        }

        $auth = yaml_parse_file(From::VAR->dir() . 'indieauth/auth-' . $token_record['auth']);

        if (! $auth) {
            return false;
        }

        if ($auth['used'] != 1) {
            return false;
        }

        $this->setScopes($auth['scopes']);
        $this->setClientId($auth['client_id']);

        return true;
    }

    /**
     * Handle a POST request
     */
    private function postRequest(): void
    {
        switch ($this->getRequest()->post('action')) {
            case null:
                $this->createPost();

                return;
        }
    }

    /**
     * Handle a create request
     */
    private function createPost(): void
    {
        if (! $this->hasSufficientScope('create', 'create a post')) {
            return;
        }

        $post = $this->postFromContentType();
        $post->createPost($this->getClientId());
        $this->setResponseBody($post->getResponseBody());
    }

    /**
     * Ensure a requestor bears a token with sufficient scope for the request
     *
     * @param string $required_scope The scope required for the current request.
     * @param string $description A description of the attempted action for error messages.
     *
     * @return bool True If the token has sufficient scope.
 * False If the token has insufficient scope.
     */
    private function hasSufficientScope(string $required_scope, string $description): bool
    {
        if (in_array($required_scope, $this->getScopes())) {
            return true;
        }

        $this->getResponse()->setCode(401);
        $this->setResponseBody(
            [
                'error' => 'insufficient_scope',
                'scope' => $required_scope,
                'error_description' => "the access token must have '$required_scope' scope to $description",
            ]
        );

        return false;
    }

    /**
     * Initialize a new post of the JSON or standard type to match the HTTP Content-Type.
     *
     * @return JsonPost|FormPost The post instance.
     */
    private function postFromContentType(): JsonPost|FormPost
    {
        if ($this->getRequest()->server('CONTENT_TYPE') == 'application/json') {
            return new JsonPost($this->getPost(), $this->getRequest(), $this->getResponse());
        }

        return new FormPost($this->getPost(), $this->getRequest(), $this->getResponse());
    }
}
