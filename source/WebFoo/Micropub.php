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
				$this->_getRequest();
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
	 * Handle a GET request
	 *
	 * @return void
	 */
	private function _getRequest()
	{
		switch($this->getRequest()->get('q')){
			case 'config':
				$this->_configQuery();
				return;

			case 'source':
				$this->_sourceQuery();
				return;
		}
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
	 * Respond to a configuration query
	 *
	 * @return void
	 */
	private function _configQuery()
	{
		$this->setResponse(
			array(
				'syndicate-to' => array(),
				'media-endpoint' => '',
			)
		);
	}

	/**
	 * Respond to a source query
	 *
	 * @return void
	 */
	private function _sourceQuery()
	{
		if(!$this->_checkSourceQueryURLContent()){
			return;
		}

		$yaml = array();
		if(!preg_match('/^(?m)(---$.*^...)$/Us', $this->getContent(), $yaml)){
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return;
		}

		$this->setFrontMatter(yaml_parse($yaml[1]));
		$this->setContent(trim(substr($this->getContent(), strlen($yaml[1]))));

		$this->_fillSourceQueryResponseProperties();
	}

	/**
	 * Ensure the source content query has a valid URL and matches available content
	 *
	 * @return boolean True  If the source query has a valid URL.
	 *                 False If the source query does not have a valid URL.
	 */
	private function _checkSourceQueryURLContent()
	{
		if(!$this->getRequest()->get('url')){
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the source content query must include a post URL',
				)
			);
			return false;
		}

		$url_parts = parse_url($this->getRequest()->get('url'));
		if(!isset($url_parts['path'])){
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'there is no post at the requested URL',
				)
			);
			return false;
		}

		$matches = array();
		if(!preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)/', $url_parts['path'], $matches)){
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'there is no post at the requested URL',
					'url' => $this->getRequest()->get('url'),
				)
			);
			return false;
		}

		if(!file_exists(CONTENT_ROOT . $matches[1] . 'web.foo')){
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'there is no post at the requested URL',
				)
			);
			return false;
		}

		$this->setContent(file_get_contents(CONTENT_ROOT . $matches[1] . 'web.foo'));

		return true;
	}

	/**
	 * Copy the requested properties into the response object
	 *
	 * @return void
	 */
	private function _fillSourceQueryResponseProperties()
	{
		$front_matter = $this->getFrontMatter();

		if(!$this->getRequest()->get('properties')){
			$response = array(
				'type' => $front_matter['item']['type'],
				'properties' => $front_matter['item']['properties'],
			);

			$response['properties']['content'][] = $this->getContent();

			$this->setResponse($response);

			return;
		}

		$response = array();
		if(is_array($this->getRequest()->get('properties'))){
			foreach($this->getRequest()->get('properties') as $property){
				switch($property){
					case 'content':
						$response['properties']['content'][] = $this->getContent();
						continue 2;

					case 'type':
						$response['type'] = $front_matter['item']['type'];
						continue 2;
				}
				if(isset($front_matter['item']['properties'][$property])){
					$response['properties'][$property] = $front_matter['item']['properties'][$property];
				}
			}
			$this->setResponse($response);
			return;
		}
		if(isset($front_matter['item']['properties'][$this->getRequest()->get('properties')])){
			$response['properties'][$this->getRequest()->get('properties')] = $front_matter['item']['properties'][$this->getRequest()->get('properties')];
		}
		$this->setResponse($response);
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
		$post->createPost($this->getConfig(), $this->getRequest(), $this->getClientId());
		$this->setResponse($post->getResponse());
	}

	/**
	 * Initialize a new post of the JSON or standard type to match the HTTP Content-Type.
	 *
	 * @return Micropub\Post The post instance.
	 */
	private function _postFromContentType()
	{
		if('application/json' == $this->getRequest()->server('CONTENT_TYPE')){
			return new Micropub\Post\Json();
		}

		return new Micropub\Post();
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

}
