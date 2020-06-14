<?php
/**
 * The IndieAuth class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The IndieAuth class implements an IndieAuth server
 */
class IndieAuth
{

	use Aether;

	/**
	 * Send the IndieAuth authorization point HTTP link-rel header
	 *
	 * @param Config $config The active configuration.
	 */
	public function __construct(Config $config)
	{
		$this->setConfig($config);

		header('Link: </auth/>; rel="authorization_endpoint", </token/>; rel="token_endpoint"');
	}

	/**
	 * Handle an incoming authentication request
	 *
	 * @param Request $request The current HTTP request.
	 *
	 * @return void
	 */
	public function authenticationRequest(Request $request)
	{
		$this->setRequest($request);
		$this->setValidation(new IndieAuth\Validation($request));

		if('GET' == $request->getMethod()){
			$this->_startAuthentication();
		}

		if('POST' == $request->getMethod()){
			if($request->post('ssrv') && $request->session('ssrv') == $request->post('ssrv')){
				$this->_approveAuthenticationRequest();
			}
		}
	}

	/**
	 * Consider an incoming authentication request
	 *
	 * @return void
	 */
	private function _startAuthentication()
	{
		$validation = $this->getValidation();

		$validation->authenticationRequest($this->getConfig()->getMe());
		$this->isValid($validation->isValid());

		if(!$this->isValid()){
			$this->setErrors($validation->getErrors());
		}

		$this->setMe($validation->getMe());
		$this->setClientId($validation->getClientId());
		$this->setRedirectUri($validation->getRedirectUri());
		$this->setState($validation->getState());
		$this->setResponseType($validation->getResponseType());
		$this->setScopes($validation->getScopes());
	}

	/**
	 * Generate an authentication code and redirect to the client
	 *
	 * @throws Exception\Redirect A HTTP redirect is required.
	 *
	 * @return void
	 */
	private function _approveAuthenticationRequest()
	{
		$request = $this->getRequest();
		$client_id = $request->post('client_id');
		$redirect_uri = $request->post('redirect_uri');
		$state = $request->post('state');

		$code = str_replace(
			array('+', '/'),
			array('-', '_'),
			substr(
				bin2hex(openssl_random_pseudo_bytes(16)),
				0,
				20
			)
		);

		$approval = array(
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'code' => password_hash($code, PASSWORD_DEFAULT),
			'expires' => now() + 600,
			'used' => 0,
			'scopes' => $request->post('scopes'),
		);

		$filename = hash('sha1', "[$client_id][$redirect_uri][$code]");

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		yaml_emit_file(VAR_ROOT . '/indieauth/auth-' . $filename, $approval);

		throw new Exception\Redirect($redirect_uri . '?code=' . $code . '&state=' . $state);
	}

	/**
	 * Handle an incoming authorization verfication request
	 *
	 * @param Request $request The current HTTP request.
	 *
	 * @return boolean True  If the authorization code request is good.
	 *                 False If the authorization code request is not good.
	 */
	public function authorizationCodeVerificationRequest(Request $request)
	{
		header('Content-Type: application/json; charset=UTF-8');

		if(!$this->_indieAuthRequestHasParams($request, 'authorization code verification')){
			return false;
		}

		$client_id = $request->post('client_id');
		$redirect_uri = $request->post('redirect_uri');
		$code = $request->post('code');

		$filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
		if(!file_exists(VAR_ROOT . 'indieauth/auth-' . $filename)){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the authorization code verification request could not be matched to an approved authentication response',
				)
			);
			return false;
		}

		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);

		if((now() - 600) > $approval['expires']){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the authorization code verification request matched an approved authentication response that has already expired (10 mins)',
				)
			);
			return false;
		}

		$this->setResponse(
			array(
				'me' => $this->getConfig()->getMe(),
			)
		);

		$approval['used']++;

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		yaml_emit_file(VAR_ROOT . 'indieauth/auth-' . $filename, $approval);

		if(1 != $approval['used']){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the authorization code verification request matched an approved authentication response that has already been used',
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Check that an authorization verfication or token request has required parameters
	 *
	 * @param Request $request The current HTTP request.
	 * @param string  $name    The name of the request for use in error messages.
	 *
	 * @return boolean True  If the request has required parameters.
	 *                 False If the request is missing required parameters.
	 */
	private function _indieAuthRequestHasParams(Request $request, string $name)
	{
		if(!$request->post('code')){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the ' . $name . ' request was missing the code parameter',
				)
			);
			return false;
		}

		if(!$request->post('client_id')){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the ' . $name . ' request was missing the client_id parameter',
				)
			);
			return false;
		}

		if(!$request->post('redirect_uri')){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the ' . $name . ' request was missing the redirect_uri parameter',
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate a user profile URL with the rules of the spec
	 *
	 * @param string  $url     The user profile URL.
	 * @param Request $request The current request.
	 *
	 * @return boolean True  If the user profile URL is valid.
	 *                 False If the user profile URL is not valid.
	 */
	public function validateUserProfileURL(string $url, Request $request)
	{
		$validation = new IndieAuth\Validation($request);
		if(!$validation->userProfileURL($url)){
			$this->setErrors($validation->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Handle an incoming token request
	 *
	 * @param Request $request The current request.
	 *
	 * @return boolean True  If the token request is good.
	 *                 False If the token request is not good.
	 */
	public function tokenRequest(Request $request)
	{
		header('Content-Type: application/json; charset=UTF-8');

		if(!$this->_tokenRequestHasParams($request)){
			return false;
		}

		$client_id = $request->post('client_id');
		$redirect_uri = $request->post('redirect_uri');
		$code = $request->post('code');

		$filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
		if(!file_exists(VAR_ROOT . 'indieauth/auth-' . $filename)){
			$this->setResponse(
				array(
					'error' => 'invalid_grant',
					'error_description' => 'the token request could not be matched to an approved authorization response',
				)
			);
			return false;
		}

		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);

		if((now() - 600) > $approval['expires']){
			$this->setResponse(
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

		if(!isset($approval['scopes'])){
			$this->setResponse(
				array(
					'error' => 'invalid_grant',
					'error_description' => 'the token request matched an approved authentication response which authorizes no scopes',
				)
			);
			return false;
		}

		if(1 != $approval['used']){
			$this->setResponse(
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

		header('Cache-Control: no-store');
		header('Pragma: no-cache');

		$this->setResponse(
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
	 * @param Request $request The current HTTP request.
	 *
	 * @return boolean True  If the token request has required parameters.
	 *                 False If the token request is missing required parameters.
	 */
	private function _tokenRequestHasParams(Request $request)
	{
		if(!$request->post('grant_type')){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the token request was missing the grant_type parameter',
				)
			);
			return false;
		}

		if('authorization_code' != $request->post('grant_type')){
			$this->setResponse(
				array(
					'error' => 'unsupported_grant_type',
					'error_description' => 'the requested grant type is not supported here',
				)
			);
			return false;
		}

		if(!$request->post('me')){
			$this->setResponse(
				array(
					'error' => 'invalid_request',
					'error_description' => 'the token request was missing the user profile URL (me) parameter',
				)
			);
			return false;
		}

		if(!$this->_indieAuthRequestHasParams($request, 'token')){
			return false;
		}

		return true;
	}

	/**
	 * Revoke an access token if it exists
	 *
	 * @param Request $request The current HTTP request.
	 *
	 * @return void
	 */
	public function tokenRevocation(Request $request)
	{
		$token = $request->post('token');

		if(!$token){
			return;
		}

		if(!file_exists(VAR_ROOT . 'indieauth/token-' . $token)){
			return;
		}

		$access_token = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $token);

		if(!$access_token){
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

}
