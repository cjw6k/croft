<?php
/**
 * The IndieAuth\Validation class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\IndieAuth;

/**
 * The Validation class provides data validation methods to match the IndieAuth spec
 */
class Validation
{

	use \cjw6k\WebFoo\Aether;

	/**
	 * Store a local reference to the current request.
	 *
	 * @param \cjw6k\WebFoo\Request $request The current request.
	 */
	public function __construct(\cjw6k\WebFoo\Request $request)
	{
		$this->setRequest($request);
	}

	/**
	 * Ensure the authentication request matches requirements of the spec
	 *
	 * @return void
	 */
	public function authenticationRequest()
	{
		$this->isValid(false);

		$this->setURL(new Validation\URL());

		$request = $this->getRequest();
		$this->setMe($request->get('me'));
		$this->setClientId($request->get('client_id'));
		$this->setRedirectUri($request->get('redirect_uri'));
		$this->setState($request->get('state'));

		$response_type = $request->get('response_type');
		if(!$response_type){
			$response_type = 'id';
		}
		$this->setResponseType($response_type);

		if(!$this->_clientId()){
			return;
		}

		if(!$this->_redirectUri()){
			return;
		}

		if(!$this->_clientMatchesRedirect()){
			return;
		}

		if(!$this->_state()){
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
	private function _clientId()
	{
		if(null == $this->getClientId()){
			$this->mergeErrors('missing required client_id parameter');
			return false;
		}

		if(!$this->getURL()->common($this->getClientId(), 'client_id')){
			$this->setErrors($this->getURL()->getErrors());
			return false;
		}

		if(!$this->getURL()->simple($this->getClientId(), 'client_id')){
			$this->setErrors($this->getURL()->getErrors());
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
	private function _redirectUri()
	{
		if(null == $this->getRedirectUri()){
			$this->mergeErrors('missing required redirect_uri parameter');
			return false;
		}

		if(!$this->getURL()->common($this->getRedirectUri(), 'redirect_uri')){
			$this->setErrors($this->getURL()->getErrors());
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
		 * If scheme isn't set, _urlCommon will return to the caller
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
		 * If host isn't set, _urlCommon will return to the caller
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
	private function _state()
	{
		if(null == $this->getRequest()->get('state')){
			$this->mergeErrors('missing required state parameter');
			return false;
		}

		return true;
	}

	/**
	 * Validate a user profile URL with the rules of the spec
	 *
	 * @param string $url The user profile URL.
	 *
	 * @return boolean True  If the user profile URL is valid.
	 *                 False If the user profile URL is not valid.
	 */
	public function userProfileURL(string $url)
	{
		$this->setURL(new Validation\URL());

		if(!$this->getURL()->common($url, 'profile URL')){
			$this->setErrors($this->getURL()->getErrors());
			return false;
		}

		if(!$this->getURL()->simple($url, 'profile URL', false)){
			$this->setErrors($this->getURL()->getErrors());
			return false;
		}

		$url_parts = parse_url($url);

		if(isset($url_parts['port'])){
			$this->mergeErrors("profile URL must not contain a port");
			return false;
		}

		return true;
	}

}
