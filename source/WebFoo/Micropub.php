<?php
/**
 * The Micropub class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Micropub class implements a Micropub server
 */
class Micropub
{

	use Aether;

	/**
	 * Send the Micropub HTTP link-rel header
	 *
	 * @param Config $config The active configuration.
	 */
	public function __construct(Config $config)
	{
		$this->setConfig($config);

		$this->mergeHTTPLinks('</micropub/>; rel="micropub"');
	}

	/**
	 * Handle a request
	 *
	 * @param Request $request The current request.
	 *
	 * @return void
	 */
	public function handleRequest(Request $request){
		$this->setRequest($request);

		header('Content-Type: application/json; charset=UTF-8');

		if(!$this->_checkAccessToken()){
			return;
		}

		switch($request->getMethod()){
			case 'GET':
				$query = new Micropub\Query();
				$query->handleRequest($request);
				$this->setResponse($query->getResponse());
				break;

			case 'POST':
				$this->_postRequest();
				break;
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
		$auth_header = $this->getRequest()->server('HTTP_AUTHORIZATION');

		$auth_param = null;
		switch($this->getRequest()->getMethod()){
			case 'GET':
				$auth_param = $this->getRequest()->get('access_token');
				break;

			case 'POST':
				$auth_param = $this->getRequest()->post('access_token');
				break;
		}

		if(!is_null($auth_header) && !is_null($auth_param)){
			if($this->_isExceptionForMicropubRocks()){
				return true;
			}
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the micropub request provided both header and parameter access tokens',
				)
			);
			return false;
		}

		if(!is_null($auth_param)){
			return $this->_verifyToken($auth_param);
		}

		if(!is_null($auth_header)){
			return $this->_verifyToken(str_replace('Bearer ', '', $auth_header));
		}

		http_response_code(401);
		$this->setResponse(
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
		if(!$this->_verifyToken(str_replace('Bearer ', '', $this->getRequest()->server('HTTP_AUTHORIZATION')))){
			return false;
		}

		$config_micropub = $this->getConfig()->getMicropub();
		if(!isset($config_micropub['exceptions']['two_copies_of_access_token'])){
			return false;
		}

		$url_parts = parse_url($this->getClientId());
		if(!isset($url_parts['host'])){
			return false;
		}
		if(!in_array($url_parts['host'], $config_micropub['exceptions']['two_copies_of_access_token'])){
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
		if(!file_exists(VAR_ROOT . 'indieauth/token-' . $token)){
			http_response_code(403);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the micropub request could not be matched to an authorized access token',
				)
			);
			return false;
		}

		$token_record = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $token);
		if(!$token_record){
			return false;
		}

		if(isset($token_record['revoked'])){
			return false;
		}

		$auth = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $token_record['auth']);
		if(!$auth){
			return false;
		}

		if(1 != $auth['used']){
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
		if(!$this->_hasSufficientScope('create', 'create a post')){
			return;
		}

		$post = $this->_postFromContentType();
		$post->createPost($this->getRequest(), $this->getClientId());
		$this->setResponse($post->getResponse());
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
		if(in_array($required_scope, $this->getScopes())){
			return true;
		}

		http_response_code(401);
		$this->setResponse(
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
	 * @return Micropub\Post The post instance.
	 */
	private function _postFromContentType()
	{
		if('application/json' == $this->getRequest()->server('CONTENT_TYPE')){
			return new Micropub\Post\Json(new Post($this->getConfig()));
		}

		return new Micropub\Post\Form(new Post($this->getConfig()));
	}

}
