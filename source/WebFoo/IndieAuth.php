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
	 */
	public function __construct()
	{
		header('Link: </auth/>; rel="authorization_endpoint"');
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

		$validation->authenticationRequest();
		$this->isValid($validation->isValid());

		if(!$this->isValid()){
			$this->setErrors($validation->getErrors());
		}

		$this->setMe($validation->getMe());
		$this->setClientId($validation->getClientId());
		$this->setRedirectUri($validation->getRedirectUri());
		$this->setState($validation->getState());
		$this->setResponseType($validation->getResponseType());
	}

	/**
	 * Generate an authentication code and redirect to the client
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
		);

		$filename = hash('sha1', "[$client_id}][$redirect_uri][$code]");

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		yaml_emit_file(VAR_ROOT . '/indieauth/auth-' . $filename, $approval);

		header('Location: ' . $redirect_uri . '?code=' . $code . '&state=' . $state);
	}

}
