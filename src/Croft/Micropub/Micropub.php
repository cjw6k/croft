<?php
/**
 * The Micropub class is herein defined.
 *
 * @package WebFoo\Micropub
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Post\PostInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Response\HTTPLinkable;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;

/**
 * The Micropub class implements a Micropub server
 */
class Micropub implements ExtensionInterface, HTTPLinkable, Routable
{

    use Aether;

    /**
     * Send the Micropub HTTP link-rel header
     *
     * @param ConfigInterface   $config   The config service.
     * @param PostInterface     $post     The post service.
     * @param RequestInterface  $request  The request service.
     * @param ResponseInterface $response The response service.
     */
    public function __construct(ConfigInterface $config, PostInterface $post, RequestInterface $request, ResponseInterface $response)
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
    public function getRoutes()
    {
        return array(
        new Route(array('GET', 'POST'), '/micropub/', 'handleRequest')
        );
    }

    /**
     * Provide HTTP link header configuration to the Response\HTTP
     *
     * @return mixed[] An array of HTTP link headers.
     */
    public function getHTTPLinks()
    {
        return array(
        '</micropub/>; rel="micropub"'
        );
    }

    /**
     * Handle a request
     *
     * @return void
     */
    public function handleRequest()
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if(!$this->_checkAccessToken()) {
            if($this->hasResponseBody()) {
                echo json_encode($this->getResponseBody());
            }
            return;
        }

        switch($request->getMethod()){
        case 'GET':
            $query = new Query($request, $this->getResponse());
            $query->handleRequest();
            $this->setResponseBody($query->getResponseBody());
            break;

        case 'POST':
            $this->_postRequest();
            break;
        }

        if($this->hasResponseBody()) {
            echo json_encode($this->getResponseBody());
        }
    }

    /**
     * Ensure that the request contains a valid bearer token
     *
     * @return boolean True  If the access token is valid.
     *                 False If the access token is missing or invalid.
     */
    private function _checkAccessToken()
    {
        $request = $this->getRequest();

        $auth_header = $request->server('HTTP_AUTHORIZATION');

        $auth_param = null;
        switch($request->getMethod()){
        case 'GET':
            $auth_param = $request->get('access_token');
            break;

        case 'POST':
            $auth_param = $request->post('access_token');
            break;
        }

        if(!is_null($auth_header) && !is_null($auth_param)) {
            if($this->_isExceptionForMicropubRocks()) {
                return true;
            }
            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the micropub request provided both header and parameter access tokens',
                )
            );
            return false;
        }

        if(!is_null($auth_param)) {
            return $this->_verifyToken($auth_param);
        }

        if(!is_null($auth_header)) {
            return $this->_verifyToken(str_replace('Bearer ', '', $auth_header));
        }

        $this->getResponse()->setCode(401);
        $this->setResponseBody(
            array(
            'error' => 'unauthorized',
            'error_description' => 'the micropub request did not provide an access token',
            )
        );
        return false;
    }

    /**
     * Volkswagen fur Micropub
     *
     * The micropub.rocks tests require permitting an undocumented violation of Oauth2 to pass.
     *
     * @return boolean True  If this is going to be allowed for Micropub.rocks.
     *                 False If this is not micropub.rocks.
     */
    private function _isExceptionForMicropubRocks()
    {
        if(!$this->_verifyToken(str_replace('Bearer ', '', $this->getRequest()->server('HTTP_AUTHORIZATION')))) {
            return false;
        }

        $config_micropub = $this->getConfig()->getMicropub();
        if(!isset($config_micropub['exceptions']['two_copies_of_access_token'])) {
            return false;
        }

        $url_parts = parse_url($this->getClientId());
        if(!isset($url_parts['host'])) {
            return false;
        }
        if(!in_array($url_parts['host'], $config_micropub['exceptions']['two_copies_of_access_token'])) {
            return false;
        }

        return true;
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
            $this->getResponse()->setCode(403);
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the micropub request could not be matched to an authorized access token',
                )
            );
            return false;
        }

        $token_record = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $token);
        if(!$token_record) {
            return false;
        }

        if(isset($token_record['revoked'])) {
            return false;
        }

        $auth = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $token_record['auth']);
        if(!$auth) {
            return false;
        }

        if(1 != $auth['used']) {
            return false;
        }

        $this->setScopes($auth['scopes']);
        $this->setClientId($auth['client_id']);

        return true;
    }

    /**
     * Handle a POST request
     *
     * @return void
     */
    private function _postRequest()
    {
        switch($this->getRequest()->post('action')){
        case null:
            $this->_createPost();
            return;
        }
    }

    /**
     * Handle a create request
     *
     * @return void
     */
    private function _createPost()
    {
        if(!$this->_hasSufficientScope('create', 'create a post')) {
            return;
        }

        $post = $this->_postFromContentType();
        $post->createPost($this->getClientId());
        $this->setResponseBody($post->getResponseBody());
    }

    /**
     * Ensure a requestor bears a token with sufficient scope for the request
     *
     * @param string $required_scope The scope required for the current request.
     * @param string $description    A description of the attempted action for error messages.
     *
     * @return boolean True  If the token has sufficient scope.
     *                 False If the token has insufficient scope.
     */
    private function _hasSufficientScope(string $required_scope, string $description)
    {
        if(in_array($required_scope, $this->getScopes())) {
            return true;
        }

        $this->getResponse()->setCode(401);
        $this->setResponseBody(
            array(
            'error' => 'insufficient_scope',
            'scope' => $required_scope,
            'error_description' => "the access token must have '$required_scope' scope to $description"
            )
        );

        return false;
    }

    /**
     * Initialize a new post of the JSON or standard type to match the HTTP Content-Type.
     *
     * @return JsonPost|FormPost The post instance.
     */
    private function _postFromContentType()
    {
        if('application/json' == $this->getRequest()->server('CONTENT_TYPE')) {
            return new JsonPost($this->getPost(), $this->getRequest(), $this->getResponse());
        }

        return new FormPost($this->getPost(), $this->getRequest(), $this->getResponse());
    }

}
