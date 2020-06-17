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
	 * Handle a create request
	 *
	 * @throws Exception\Redirect A HTTP redirect to the new post.
	 *
	 * @return void
	 */
	private function _createPost()
	{
		if(!$this->_hasSufficientScope('create', 'create a post')){
			return;
		}

		if(!$this->getRequest()->post('content')){
			http_response_code(400);
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => "the content parameter is required to create a post"
				)
			);
			return;
		}

		if(!$this->_makeContentPath(now())){
			return;
		}

		if(!$this->_takeNextPostId()){
			return;
		}

		mkdir($this->getContentPath() . $this->getContentId() . '/');
		file_put_contents($this->getContentPath() . $this->getContentId() . '/web.foo', $this->getRequest()->post('content'));

		http_response_code(201);
		throw new Exception\Redirect(rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/');
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
	 * Create a path in content/ for year/month/day of the new post if it doesn't exist
	 *
	 * @param integer $pub_ts The publication date of this post as unix timestamp.
	 *
	 * @return boolean True  If the path exists or has been created.
	 *                 False If the path does not exist and could not be created.
	 */
	private function _makeContentPath(int $pub_ts)
	{
		$pub_dt = getdate($pub_ts);

		if(!$pub_dt || !isset($pub_dt['year']) || !isset($pub_dt['mon']) || !isset($pub_dt['mday'])){
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$this->setUrlPath($pub_dt['year'] . '/' . str_pad($pub_dt['mon'], 2, '0', STR_PAD_LEFT) . '/' . str_pad($pub_dt['mday'], 2, '0', STR_PAD_LEFT) . '/');
		$this->setContentPath(CONTENT_ROOT . $this->getUrlPath());

		if(file_exists($this->getContentPath())){
			return true;
		}

		if(!mkdir($this->getContentPath(), 0755, true)){
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$yaml = fopen($this->getContentPath() . 'thisday.yml', 'c+');
		if(!flock($yaml, LOCK_EX)){
			fclose($yaml);
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$this_day = array(
			'next_id' => 1
		);

		ftruncate($yaml, 0);
		rewind($yaml);
		fwrite($yaml, yaml_emit($this_day));
		fflush($yaml);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($yaml, LOCK_UN);
		fclose($yaml);

		return true;
	}

	/**
	 * Get the next id from the record of this day's posts and increment the next_id in the record
	 *
	 * @return boolean True  If the claim on the next id succeeded.
	 *                 False If the claim on the next id failed.
	 */
	private function _takeNextPostId()
	{
		$yaml = fopen($this->getContentPath() . 'thisday.yml', 'c+');
		if(!$yaml){
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		if(!flock($yaml, LOCK_EX)){
			fclose($yaml);
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		rewind($yaml);
		$this_day_raw = fread($yaml, filesize($this->getContentPath() . 'thisday.yml'));
		$this_day = yaml_parse($this_day_raw);
		ftruncate($yaml, 0);
		rewind($yaml);

		$this->setContentId($this_day['next_id']++);

		fwrite($yaml, yaml_emit($this_day));
		fflush($yaml);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($yaml, LOCK_UN);
		fclose($yaml);

		return true;
	}

}
