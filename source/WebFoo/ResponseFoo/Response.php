<?php
/**
 * The Response class is herein defined.
 *
 * @package	WebFoo\ResponseFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\ResponseFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Response\ResponseInterface;

/**
 * The Response class composes and sends the response to the client when complete
 */
class Response implements ResponseInterface
{

	use Aether;

	/**
	 * Turn on output buffering.
	 *
	 * @return void
	 */
	public function __construct()
	{
		ob_start();
	}

	/**
	 * Fill in HTTP headers as needed and send the response to the client
	 *
	 * @return void
	 */
	public function send()
	{
		if(session_id()){
			session_write_close();
		}

		ignore_user_abort(true);

		$this->setBody(ob_get_clean());

		$this->_setContentLength();

		if($this->hasHeaders()){
			$this->_sendHeaders();
		}

		if($this->hasBody()){
			$this->_sendBody();
		}

		flush();
		fastcgi_finish_request();
	}

	/**
	 * Calculate the length of the response body and set the content-length HTTP header
	 *
	 * @return void
	 */
	private function _setContentLength()
	{
		$content_length = $this->hasBody() ? strlen($this->getBody()) : 0;
		$this->mergeHeaders("Content-Length: $content_length");
	}

	/**
	 * Send the HTTP headers
	 *
	 * @return void
	 */
	private function _sendHeaders()
	{
		if($this->hasCode()){
			http_response_code($this->getCode());
		}

		foreach($this->getHeaders() as $header){
			header($header);
		}
	}

	/**
	 * Send the response body
	 *
	 * @return void
	 */
	private function _sendBody()
	{
		echo $this->getBody();
	}

}
