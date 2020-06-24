<?php
/**
 * The Micropub\Query class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub;

/**
 * The Micropub\Query class handles the GET queries of Micropub
 */
class Query
{

	use \cjw6k\WebFoo\Aether;

	/**
	 * Handle a GET request
	 *
	 * @param \cjw6k\WebFoo\Request $request The current request.
	 *
	 * @return void
	 */
	public function handleRequest(\cjw6k\WebFoo\Request $request)
	{
		$this->setRequest($request);

		switch($request->get('q')){
			case 'config':
				$this->_configQuery();
				return;

			case 'source':
				$this->_sourceQuery();
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

}
