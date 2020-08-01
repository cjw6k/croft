<?php
/**
 * The Webmention class is herein defined.
 *
 * @package	WebFoo\Webmention
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Webmention;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Async\Asyncable;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Response\HTTPLinkable;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Webmention class implements a webmention receiver
 */
class Webmention implements ExtensionInterface, Asyncable, HTTPLinkable, Routable
{

	use Aether;

	/**
	 * Send the Webmention HTTP link-rel header
	 *
	 * @param ConfigInterface   $config   The active configuration.
	 * @param RequestInterface  $request  The current request.
	 * @param ResponseInterface $response The response.
	 * @param StorageInterface  $storage  The storage service.
	 */
	public function __construct(ConfigInterface $config, RequestInterface $request, ResponseInterface $response, StorageInterface $storage)
	{
		$this->setConfig($config);
		$this->setRequest($request);
		$this->setResponse($response);
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
			new Route('POST', '/webmention/', 'handleRequest')
		);
	}

	/**
	 * Provide HTTP link header configuration
	 *
	 * @return mixed[] An array of HTTP link headers.
	 */
	public function getHTTPLinks(){
		return array(
			'</webmention/>; rel="webmention"'
		);
	}

	/**
	 * Handle a request
	 *
	 * @return void
	 */
	public function handleRequest(){
		$validation = new Validation($this->getConfig(), $this->getRequest());

		if(!$validation->request()){
			$this->getResponse()->setCode(400);
			$this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
			echo $validation->getResponseBody();
			return;
		}

		$this->setTarget($validation->getTarget());
		$this->setSource($validation->getSource());
		$this->setTargetParts($validation->getTargetParts());
		$this->setSourceParts($validation->getSourceParts());

		if($this->_targetsRestrictedPath()){
			$this->getResponse()->setCode(400);
			$this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
			echo 'Error: the target URL does not accept webmentions';
			return;
		}

		if(!$this->_targetsContentThatExistsHere()){
			$this->getResponse()->setCode(400);
			$this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
			echo 'Error: the target URL is for content that does not exist here';
			return;
		}

		$this->_spoolIncoming();
		$this->getResponse()->setCode(202);
	}

	/**
	 * Prevent webmentions to restricted paths.
	 *
	 * @return boolean True  If the webmention targets a restricted path.
	 *                 False If the webmention does not target a restricted path.
	 */
	private function _targetsRestrictedPath()
	{
		switch($this->getTargetParts()['path']){
			case '/auth/':
			case '/token/':
			case '/micropub/':
			case '/login/':
			case '/logout/':
			case '/webmention/':
				return true;
		}

		$matches = array();
		if(!preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)(?:media\/(.*))?/', $this->getTargetParts()['path'], $matches)){
			return false;
		}

		if(isset($matches[2])){
			return true;
		}

		return false;
	}

	/**
	 * Ensure webmention targets content that has been posted here
	 *
	 * @return boolean True  If the webmention targets valid content.
	 *                 False If the webmention does not target valid content.
	 */
	private function _targetsContentThatExistsHere()
	{
		$matches = array();
		if(!preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)/', $this->getTargetParts()['path'], $matches)){
			return false;
		}

		if(!file_exists(CONTENT_ROOT . $matches[1] . 'web.foo')){
			return false;
		}

		$this->setTargetPostRecordPath(CONTENT_ROOT . $matches[1]);

		return true;
	}

	/**
	 * Record a valid incoming webmention for later processing
	 *
	 * @return void
	 */
	private function _spoolIncoming()
	{
		$request = $this->getRequest();
		$webmention = array(
			'target' => $this->getTarget(),
			'source' => $this->getSource(),
			'request' => array(
				'query_string' => $request->server('QUERY_STRING'),
				'referer' => $request->server('HTTP_REFERER'),
				'user_agent' => $request->server('HTTP_USER_AGENT'),
				'remote_addr' => $request->server('REMOTE_ADDR'),
			)
		);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		yaml_emit_file(VAR_ROOT . 'webmention/incoming-' . uniqid(), $webmention);
	}

	/**
	 * Process webmentions from the spool
	 *
	 * @return void
	 */
	public function async()
	{
		$this->_processIncoming();
	}

	/**
	 * Process incoming webmentions from the spool
	 *
	 * @return void
	 */
	private function _processIncoming()
	{
		$incoming = glob(VAR_ROOT . 'webmention/incoming-*');

		foreach($incoming as $spooled){
			$webmention = yaml_parse_file($spooled);
			$this->setTarget($webmention['target']);
			$this->setSource($webmention['source']);

			$this->setTargetParts(parse_url($webmention['target']));
			if(!$this->_targetsContentThatExistsHere()){
				unlink($spooled);
				continue;
			}

			if(!$this->_sourceContainsTargetLink()){
				unlink($spooled);
				continue;
			}

			if(!$this->_recordWebmention()){
				continue;
			}

			unlink($spooled);
		}
	}

	/**
	 * Check that the webmention source contains the target URL
	 *
	 * @return boolean True  If the source contains the target URL.
	 *                 False If the source does not contain the target URL.
	 */
	private function _sourceContainsTargetLink()
	{
		$curl_handle = curl_init($this->getSource());
		curl_setopt_array(
			$curl_handle,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_MAXREDIRS => 5,
				CURLOPT_REFERER => $this->getConfig()->getMe(),
				CURLOPT_USERAGENT => "webfoo (webmention; endpoint discovery)",
			)
		);
		$response = curl_exec($curl_handle);
		$status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		curl_close($curl_handle);

		if(200 != $status){
			return false;
		}

		if(!$response || !is_string($response) || (false === strpos($response, $this->getTarget()))){
			return false;
		}

		return true;
	}

	/**
	 * Update the post record with received webmentions
	 *
	 * @return boolean True  The webmention has been recorded.
	 *                 False Recording the webmention has failed.
	 */
	private function _recordWebmention()
	{
		$webmentions = array(
			'generic' => array(
				'count' => 0,
				'items' => array(),
			),
			'response' => array(
				'count' => 0,
				'items' => array(
					'like' => array(
						'count' => 0,
						'items' => array(),
					),
					'bookmark' => array(
						'count' => 0,
						'items' => array(),
					),
					'reply' => array(
						'count' => 0,
						'items' => array(),
					),
				),
			),
			'repost' => array(
				'count' => 0,
				'items' => array(),
			),
		);

		$webmentions_file = fopen($this->getTargetPostRecordPath() . 'web.mentions', 'c+');
		if(!$webmentions_file){
			return false;
		}

		if(!flock($webmentions_file, LOCK_EX)){
			fclose($webmentions_file);
			return false;
		}

		$contents = fread($webmentions_file, filesize($this->getTargetPostRecordPath() . 'web.mentions'));
		if(0 < strlen($contents)){
			$yaml = yaml_parse($contents);
			if($yaml){
				$webmentions = $yaml;
			}
			ftruncate($webmentions_file, 0);
			rewind($webmentions_file);
		}

		$webmentions['generic']['count']++;
		$webmentions['generic']['items'][] = $this->getSource();

		fwrite($webmentions_file, yaml_emit($webmentions));
		fflush($webmentions_file);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($webmentions_file, LOCK_UN);
		fclose($webmentions_file);

		return true;
	}

}
