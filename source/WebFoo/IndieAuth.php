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
	 * Validate an incoming authentication request
	 *
	 * @param Request $request The current HTTP request.
	 *
	 * @return void
	 */
	public function authenticationRequest(Request $request)
	{
		$this->setRequest($request);

		$this->setMe($request->get('me'));
		$this->setClientId($request->get('client_id'));
		$this->setRedirectUri($request->get('redirect_uri'));
		$this->setState($request->get('state'));

		$response_type = $request->get('response_type');
		if(!$response_type){
			$response_type = 'id';
		}
		$this->setResponseType($response_type);

		$this->_validateAuthenticationRequest();
	}

	/**
	 * Ensure the authentication request matches requirements of the spec
	 *
	 * @return void
	 */
	private function _validateAuthenticationRequest()
	{
		$this->isValid(false);

		if(!$this->_validateClientId()){
			return;
		}

		if(!$this->_validateRedirectUri()){
			return;
		}

		if(!$this->_clientMatchesRedirect()){
			return;
		}

		if(!$this->_validateState()){
			return;
		}

		$this->isValid(true);
	}

	/**
	 * Ensure the provided client_id matches requirements of the spec
	 *
	 * @return boolean True  The client_id is valid.
	 *                 False The client_id is not valid.
	 */
	private function _validateClientId()
	{
		if(null == $this->getClientId()){
			$this->mergeErrors('missing required client_id parameter');
			return false;
		}

		if(!$this->_validateURLCommon($this->getClientId(), 'client_id')){
			return false;
		}

		if(!$this->_validateURL()){
			return false;
		}

		return true;
	}

	/**
	 * Ensure the URL has an acceptable format according to the spec.
	 *
	 * @param string $url  The URL to validate.
	 * @param string $name The name of the parameter for use in error messages.
	 *
	 * @return boolean True  If the URL is acceptable.
	 *                 False If the URL is not acceptable.
	 */
	private function _validateURLCommon(string $url, string $name)
	{
		if(false === filter_var($url, FILTER_VALIDATE_URL)){
			$this->mergeErrors($name . ' must be a URL');
			return false;
		}

		$url_parts = parse_url($url);
		if(!$url_parts || !isset($url_parts['scheme']) || !isset($url_parts['host'])){
			$this->mergeErrors($name . ' must be a URL');
			return false;
		}

		if('https' != strtolower($url_parts['scheme']) && 'http' != strtolower($url_parts['scheme'])){
			$this->mergeErrors($name . ' must use HTTP or HTTPS');
		}

		return true;
	}

	/**
	 * Ensure the provided client_id matches URL requirements of the spec
	 *
	 * @return boolean True  The client_id is a valid client_id URL.
	 *                 False The client_id is not a valid client_id URL.
	 */
	private function _validateURL()
	{
		$url_parts = parse_url($this->getClientId());

		if(isset($url_parts['fragment'])){
			$this->mergeErrors("client_id must not contain a fragment");
		}

		if(isset($url_parts['user']) || isset($url_parts['pass'])){
			$this->mergeErrors("client_id must not contain a username or password");
		}

		if(!$this->_validateClientIdDomain($url_parts['host'])){
			return false;
		}

		if(!isset($url_parts['path'])){
			$this->mergeErrors("client_id must include a path");
			return false;
		}

		$path = '/' . trim($url_parts['path'], '/') . '/';
		if(false !== strpos($path, '/./') || false !== strpos($path, '/../')){
			$this->mergeErrors("client_id must not include relative components in the path");
		}

		if($this->hasErrors()){
			return false;
		}

		return true;
	}

	/**
	 * Ensure the client_id domain is acceptable according to the spec.
	 *
	 * @param string $host The host part of the URL.
	 *
	 * @return boolean True  If the domain is acceptable.
	 *                 False If the domain is not acceptable.
	 */
	private function _validateClientIdDomain(string $host)
	{
		if(false === filter_var($host, FILTER_VALIDATE_DOMAIN)){
			$this->mergeErrors("client_id must have a valid domain name");
		}

		if(false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
			if('127.0.0.1' != $host){
				$this->mergeErrors("client_id must not be an IPV4 address");
			}
		}

		if(false !== filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
			if('[::1]' != $host){
				$this->mergeErrors("client_id must not be an IPV6 address");
			}
		}

		if($this->hasErrors()){
			return false;
		}

		return true;
	}

	/**
	 * Ensure the provided redirect_uri matches requirements of the spec
	 *
	 * @return boolean True  The redirect_uri is valid.
	 *                 False The redirect_uri is not valid.
	 */
	private function _validateRedirectUri()
	{
		if(null == $this->getRedirectUri()){
			$this->mergeErrors('missing required redirect_uri parameter');
			return false;
		}

		if(!$this->_validateURLCommon($this->getRedirectUri(), 'redirect_uri')){
			return false;
		}

		return true;
	}

	/**
	 * Ensure the client_id and redirect_uri are compatible according to the spec.
	 *
	 * @return boolean True  If the parameters are compatible.
	 *                 False If the parameters are not compatible.
	 */
	private function _clientMatchesRedirect()
	{
		$client_id_parts = parse_url(strtolower($this->getClientId()));
		$redirect_uri_parts = parse_url(strtolower($this->getRedirectUri()));

		/**
		 * If scheme isn't set, _validateURLCommon will return to the caller
		 *
		 * @psalm-suppress PossiblyUndefinedArrayOffset
		 */
		if($client_id_parts['scheme'] != $redirect_uri_parts['scheme']){
			$this->mergeErrors('client_id and redirect_uri must be on the same domain');
			return false;
		}

		if(isset($client_id_parts['port']) xor isset($redirect_uri_parts['port'])){
			$this->mergeErrors('client_id and redirect_uri must be on the same domain');
			return false;
		}

		if(isset($client_id_parts['port']) && isset($redirect_uri_parts['port'])){
			if($client_id_parts['port'] != $redirect_uri_parts['port']){
				$this->mergeErrors('client_id and redirect_uri must be on the same domain');
				return false;
			}
		}

		/**
		 * If host isn't set, _validateURLCommon will return to the caller
		 *
		 * @psalm-suppress PossiblyUndefinedArrayOffset
		 */
		if($client_id_parts['host'] == $redirect_uri_parts['host']){
			return true;
		}

		if(0 !== strpos(strrev($redirect_uri_parts['host']), strrev($client_id_parts['host']))){

			// should check for registered redirect_uri {https://indieauth.spec.indieweb.org/#redirect-url}

			$this->mergeErrors('client_id and redirect_uri must be on the same domain');
			return false;
		}

		return true;
	}

	/**
	 * Ensure the state parameter is present in the request
	 *
	 * @return boolean True  The state parameter is present.
	 *                 False The state parameter is missing.
	 */
	private function _validateState()
	{
		if(null == $this->getRequest()->get('state')){
			$this->mergeErrors('missing required state parameter');
			return false;
		}

		return true;
	}

}
