<?php
/**
 * The IndieAuth class is herein defined.
 *
 * @package	WebFoo\IndieAuth
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\IndieAuth;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Exception\Redirect;
use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Response\HTTPLinkable;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Session\SessionInterface;
use \cjw6k\WebFoo\Setup\Setup;
use \cjw6k\WebFoo\Setup\Setupable;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The IndieAuth class implements an IndieAuth server
 */
class IndieAuth implements ExtensionInterface, HTTPLinkable, Setupable, Routable
{

	use Aether;

	/**
	 * Send the IndieAuth authorization point HTTP link-rel header
	 *
	 * @param ConfigInterface   $config   The active configuration.
	 * @param RequestInterface  $request  The current request.
	 * @param ResponseInterface $response The response.
	 * @param SessionInterface  $session  The login session.
	 * @param StorageInterface  $storage  The storage service.
	 */
	public function __construct(ConfigInterface $config, RequestInterface $request, ResponseInterface $response, SessionInterface $session, StorageInterface $storage)
	{
		$this->setConfig($config);
		$this->setRequest($request);
		$this->setResponse($response);
		$this->setSession($session);
		$this->setStorage($storage);
	}

	/**
	 * Provides a list of routes to register with the Router to be serviced by this extension.
	 *
	 * @return mixed|null The list of routes to register or null if there are none.
	 */
	public function getRoutes()
	{
		return array(
			new Route(array('GET', 'POST'), '/auth/', "handleRequest"),
			new Route('POST', '/token/', 'handleTokenRequest'),
			new Route('GET', '/token/', 'handleTokenVerificationRequest'),
		);
	}

	/**
	 * Provide HTTP link header configuration to the Response\HTTP
	 *
	 * @return mixed[] An array of HTTP link headers.
	 */
	public function getHTTPLinks(){
		return array(
			'</auth/>; rel="authorization_endpoint"',
			'</token/>; rel="token_endpoint"'
		);
	}

	/**
	 * Handle a request
	 *
	 * @throws Redirect A HTTP redirect is required.
	 *
	 * @return string|void The template to render, or void to skip rendering.
	 */
	public function handleRequest()
	{
		if($this->getSession()->isLoggedIn()){
			return $this->_loggedInRequest();
		}

		if('POST' == $this->getRequest()->getMethod()){
			$authorization = new Authorization($this->getConfig(), $this->getRequest(), $this->getResponse());
			if(!$authorization->codeVerificationRequest(new Validation($this->getConfig(), $this->getRequest()))){
				$this->getResponse()->setCode(400);
			}
			echo json_encode($authorization->getResponseBody());
			return;
		}

		if(!empty($this->getRequest()->getQuery())){
			throw new Redirect('/login/?redirect_to=' . trim($this->getRequest()->getPath(), '/') . '/?' . urlencode($this->getRequest()->getQuery()));
		}

		throw new Redirect('/login/?redirect_to=' . trim($this->getRequest()->getPath(), '/') . '/?' . urlencode($this->getRequest()->getQuery()));
	}

	/**
	 * Handle a request for a logged in user
	 *
	 * @return string The template to render.
	 */
	private function _loggedInRequest()
	{
		$validation = new Validation($this->getConfig(), $this->getRequest());
		$authentication = new Authentication($this->getRequest());
		$authentication->handleRequest($validation);

		if($authentication->hasErrors()){
			$this->setErrors($authentication->getErrors());
		}

		$this->setMe($validation->getMe());
		$this->setClientId($validation->getClientId());
		$this->setRedirectUri($validation->getRedirectUri());
		$this->setState($validation->getState());
		$this->setResponseType($validation->getResponseType());
		$this->setScopes($validation->getScopes());

		return $validation->isValid() ? 'auth-good.php' : 'auth-not_good.php';
	}

	/**
	 * Setup the extension during first-time setup
	 *
	 * Errors should be directly echoed to the console.
	 *
	 * @param Setup $setup The setup service.
	 *
	 * @return boolean True  Setup may proceed.
	 *                 False Setup has failed.
	 */
	public function setup(Setup $setup)
	{
		if(!$this->validateUserProfileURL($setup->getUrl())){
			foreach($this->getErrors() as $error){
				echo 'error: ', $error, PHP_EOL;
			}
			echo 'Try \'setup.php --help\' for more information.', PHP_EOL;
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
	public function validateUserProfileURL(string $url)
	{
		$validation = new Validation($this->getConfig(), $this->getRequest());
		if(!$validation->userProfileURL($url)){
			$this->setErrors($validation->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Handle a token request
	 *
	 * @return void
	 */
	public function handleTokenRequest()
	{
		$token = new Token($this->getConfig(), $this->getRequest(), $this->getResponse());
		$token->handleRequest();
		$this->setResponseBody($token->getResponseBody());
	}

	/**
	 * Handle a token verification request
	 *
	 * @return void
	 */
	public function handleTokenVerificationRequest()
	{
		$token = new Token($this->getConfig(), $this->getRequest(), $this->getResponse());
		$token->handleVerificationRequest();
	}

}
