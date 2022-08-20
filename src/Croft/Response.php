<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Response\Response as ResponseA6a;

use function ob_start;
use function session_id;
use function session_write_close;
use function ignore_user_abort;
use function ob_get_clean;
use function flush;
use function strlen;
use function http_response_code;
use function header;

/**
 * The Response class composes and sends the response to the client when complete
 */
class Response implements ResponseA6a
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
     */
    public function send(): void
    {
        if (session_id()) {
            session_write_close();
        }

        ignore_user_abort(true);

        $this->setBody(ob_get_clean());

        $this->setContentLength();

        if ($this->hasHeaders()) {
            $this->sendHeaders();
        }

        if ($this->hasBody()) {
            $this->sendBody();
        }

        flush();
        fastcgi_finish_request();
    }

    /**
     * Calculate the length of the response body and set the content-length HTTP header
     */
    private function setContentLength(): void
    {
        $content_length = $this->hasBody() ? strlen($this->getBody()) : 0;
        $this->mergeHeaders("Content-Length: $content_length");
    }

    /**
     * Send the HTTP headers
     */
    private function sendHeaders(): void
    {
        if ($this->hasCode()) {
            http_response_code($this->getCode());
        }

        foreach ($this->getHeaders() as $header) {
            header($header);
        }
    }

    /**
     * Send the response body
     */
    private function sendBody(): void
    {
        echo $this->getBody();
    }
}
