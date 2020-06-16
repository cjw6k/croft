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
				'error' => 'invalid_request',
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
		if('config' == $this->getRequest()->get('q')){
			$this->_configQuery();
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

		return true;
	}

	/**
	 * Handle a POST request
	 *
	 * @return void
	 */
	private function _postRequest()
	{
		if('config' == $this->getRequest()->post('q')){
			$this->_configQuery();
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

}
