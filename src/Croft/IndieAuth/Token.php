<?php
/**
 * The IndieAuth\Token class is herein defined.
 *
 * @package webfoo
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\IndieAuth;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;

/**
 * The Token class provides methods to service the tokens of IndieAuth
 */
class Token
{

    use Aether;

    /**
     * Store a local reference to the current request.
     *
     * @param ConfigInterface   $config   The active configuration.
     * @param RequestInterface  $request  The current request.
     * @param ResponseInterface $response The response.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request, ResponseInterface $response)
    {
        $this->setConfig($config);
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Handle a request
     *
     * @return void
     */
    public function handleRequest()
    {
        if('revoke' == $this->getRequest()->post('action')) {
            $this->_revocation();
            return;
        }

        if(!$this->_request()) {
            $this->getResponse()->setCode(400);
        }
        echo json_encode($this->getResponseBody());
    }

    /**
     * Handle an incoming request
     *
     * @return boolean True  If the token request is good.
     *                 False If the token request is not good.
     */
    private function _request()
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if(!$this->_hasRequiredParams(new Validation($this->getConfig(), $request))) {
            return false;
        }

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $code = $request->post('code');

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
        if(!file_exists(VAR_ROOT . 'indieauth/auth-' . $filename)) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token request could not be matched to an approved authorization response',
                )
            );
            return false;
        }

        $approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);

        if((now() - 600) > $approval['expires']) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token request matched an approved authorization response that has already expired (10 mins)',
                )
            );
            return false;
        }

        $approval['used']++;

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(VAR_ROOT . 'indieauth/auth-' . $filename, $approval);

        if(!isset($approval['scopes'])) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token request matched an approved authentication response which authorizes no scopes',
                )
            );
            return false;
        }

        if(1 != $approval['used']) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token request matched an approved authorization response that has already been used',
                )
            );
            return false;
        }

        $token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(VAR_ROOT . 'indieauth/token-' . $token, array('auth' => $filename));

        $response = $this->getResponse();
        $response->mergeHeaders('Cache-Control: no-store');
        $response->mergeHeaders('Pragma: no-cache');

        $this->setResponseBody(
            array(
            'access_token' => $token,
            'token_type' => 'Bearer',
            'scope' => implode(' ', $approval['scopes']),
            'me' => $this->getConfig()->getMe(),
            )
        );

        return true;
    }

    /**
     * Check that a token request has required parameters
     *
     * @param Validation $validation Helper for validation of request parameters.
     *
     * @return boolean True  If the token request has required parameters.
     *                 False If the token request is missing required parameters.
     */
    private function _hasRequiredParams(Validation $validation)
    {
        $request = $this->getRequest();

        if(!$request->post('grant_type')) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the token request was missing the grant_type parameter',
                )
            );
            return false;
        }

        if('authorization_code' != $request->post('grant_type')) {
            $this->setResponseBody(
                array(
                'error' => 'unsupported_grant_type',
                'error_description' => 'the requested grant type is not supported here',
                )
            );
            return false;
        }

        if(!$request->post('me')) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the token request was missing the user profile URL (me) parameter',
                )
            );
            return false;
        }

        if(!$validation->indieAuthRequestHasParams('token')) {
            $this->setResponseBody($validation->getResponseBody());
            return false;
        }

        return true;
    }

    /**
     * Revoke an access token if it exists
     *
     * @return void
     */
    private function _revocation()
    {
        $token = $this->getRequest()->post('token');

        if(!$token) {
            return;
        }

        if(!file_exists(VAR_ROOT . 'indieauth/token-' . $token)) {
            return;
        }

        $access_token = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $token);

        if(!$access_token) {
            return;
        }

        $access_token['revoked'] = now();

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(VAR_ROOT . 'indieauth/token-' . $token, $access_token);
    }

    /**
     * Provide authorization details in response to requests with a valid bearer token
     *
     * @return void
     */
    public function handleVerificationRequest()
    {
        $response = $this->getResponse();
        $response->mergeHeaders('Cache-Control: no-store');
        $response->mergeHeaders('Pragma: no-cache');
        $response->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        $auth_header = $this->getRequest()->server('HTTP_AUTHORIZATION');
        if(!$auth_header) {
            $response->setCode(400);
            echo json_encode(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the token verification request did not provided a bearer token',
                )
            );
            return;
        }

        if(!$this->_verifyToken(str_replace('Bearer ', '', $auth_header))) {
            return;
        }

        echo json_encode(
            array(
            'me' => $this->getConfig()->getMe(),
            'client_id' => $this->getClientId(),
            'scope' => implode(' ', $this->getScopes()),
            )
        );
    }

    /**
     * Verify that the supplied token matches a token issued here
     *
     * @param string $token The supplied access token.
     *
     * @return boolean True  If the access token is valid here.
     *                 False If the access token is not valid here.
     */
    private function _verifyToken(string $token)
    {
        if(!file_exists(VAR_ROOT . 'indieauth/token-' . $token)) {
            $this->getResponse()->setCode(401);
            echo json_encode(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token verification request could not be matched to an issued token',
                )
            );
            return false;
        }

        $token_record = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $token);
        if(!$token_record) {
            $this->getResponse()->setCode(500);
            return false;
        }

        if(isset($token_record['revoked'])) {
            $this->getResponse()->setCode(403);
            echo json_encode(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token verification request included a bearer token which has been revoked',
                )
            );
            return false;
        }

        $auth = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $token_record['auth']);
        if(!$auth) {
            $this->getResponse()->setCode(500);
            return false;
        }

        if(1 != $auth['used']) {
            $this->getResponse()->setCode(403);
            echo json_encode(
                array(
                'error' => 'invalid_grant',
                'error_description' => 'the token verification request included a bearer token which originates from a cancelled authorization',
                )
            );
            return false;
        }

        $this->setScopes($auth['scopes']);
        $this->setClientId($auth['client_id']);

        return true;
    }

}
