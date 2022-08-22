<?php

namespace Croft\Webmention;

use A6A\Aether\Aether;
use a6a\a6a\Async\Asyncable;
use a6a\a6a\Config\Config;
use a6a\a6a\Extension\Extension;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\HttpLinkable;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Storage\Storage;
use Croft\From;

use function preg_match;
use function file_exists;
use function yaml_emit_file;
use function uniqid;
use function glob;
use function yaml_parse_file;
use function parse_url;
use function unlink;
use function curl_init;
use function curl_setopt_array;
use function curl_exec;
use function curl_getinfo;
use function curl_close;
use function is_string;
use function strpos;
use function fopen;
use function flock;
use function fclose;
use function fread;
use function filesize;
use function strlen;
use function yaml_parse;
use function ftruncate;
use function rewind;
use function fwrite;
use function yaml_emit;
use function fflush;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_TIMEOUT;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_REFERER;
use const CURLOPT_USERAGENT;
use const LOCK_EX;
use const LOCK_UN;

/**
 * The Webmention class implements a webmention receiver
 */
class Webmention implements Extension, Asyncable, HttpLinkable, Routable
{
    use Aether;

    /**
     * Send the Webmention HTTP link-rel header
     *
     * @param Config $config The active configuration.
     * @param Request $request The current request.
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Config $config, Request $request, Response $response, Storage $storage)
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
    public function getRoutes(): mixed
    {
        return [
            new Route('POST', '/webmention/', 'handleRequest'),
        ];
    }

    /**
     * Provide HTTP link header configuration
     *
     * @return array<mixed> An array of HTTP link headers.
     */
    public function getHttpLinks(): array
    {
        return [
            '</webmention/>; rel="webmention"',
        ];
    }

    /**
     * Handle a request
     */
    public function handleRequest(): void
    {
        $validation = new Validation($this->getConfig(), $this->getRequest());

        if (! $validation->request()) {
            $this->getResponse()->setCode(400);
            $this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
            echo $validation->getResponseBody();

            return;
        }

        $this->setTarget($validation->getTarget());
        $this->setSource($validation->getSource());
        $this->setTargetParts($validation->getTargetParts());
        $this->setSourceParts($validation->getSourceParts());

        if ($this->targetsRestrictedPath()) {
            $this->getResponse()->setCode(400);
            $this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
            echo 'Error: the target URL does not accept webmentions';

            return;
        }

        if (! $this->targetsContentThatExistsHere()) {
            $this->getResponse()->setCode(400);
            $this->getResponse()->mergeHeaders('Content-Type: text/plain; charset=UTF-8');
            echo 'Error: the target URL is for content that does not exist here';

            return;
        }

        $this->spoolIncoming();
        $this->getResponse()->setCode(202);
    }

    /**
     * Prevent webmentions to restricted paths.
     *
     * @return bool True If the webmention targets a restricted path.
 * False If the webmention does not target a restricted path.
     */
    private function targetsRestrictedPath(): bool
    {
        switch ($this->getTargetParts()['path']) {
            case '/auth/':
            case '/token/':
            case '/micropub/':
            case '/login/':
            case '/logout/':
            case '/webmention/':
                return true;
        }

        $matches = [];

        if (! preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)(?:media\/(.*))?/', $this->getTargetParts()['path'], $matches)) {
            return false;
        }

        return isset($matches[2]);
    }

    /**
     * Ensure webmention targets content that has been posted here
     *
     * @return bool True If the webmention targets valid content.
 * False If the webmention does not target valid content.
     */
    private function targetsContentThatExistsHere(): bool
    {
        $matches = [];

        if (! preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)/', $this->getTargetParts()['path'], $matches)) {
            return false;
        }

        if (! file_exists(From::CONTENT->dir() . $matches[1] . 'web.foo')) {
            return false;
        }

        $this->setTargetPostRecordPath(From::CONTENT->dir() . $matches[1]);

        return true;
    }

    /**
     * Record a valid incoming webmention for later processing
     */
    private function spoolIncoming(): void
    {
        $request = $this->getRequest();
        $webmention = [
            'target' => $this->getTarget(),
            'source' => $this->getSource(),
            'request' => [
                'query_string' => $request->server('QUERY_STRING'),
                'referer' => $request->server('HTTP_REFERER'),
                'user_agent' => $request->server('HTTP_USER_AGENT'),
                'remote_addr' => $request->server('REMOTE_ADDR'),
            ],
        ];

        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        yaml_emit_file(From::VAR->dir() . 'webmention/incoming-' . uniqid(), $webmention);
    }

    /**
     * Process webmentions from the spool
     */
    public function async(): void
    {
        $this->processIncoming();
    }

    /**
     * Process incoming webmentions from the spool
     */
    private function processIncoming(): void
    {
        $incoming = glob(From::VAR->dir() . 'webmention/incoming-*');

        foreach ($incoming as $spooled) {
            $webmention = yaml_parse_file($spooled);
            $this->setTarget($webmention['target']);
            $this->setSource($webmention['source']);

            $this->setTargetParts(parse_url($webmention['target']));

            if (! $this->targetsContentThatExistsHere()) {
                unlink($spooled);
                continue;
            }

            if (! $this->sourceContainsTargetLink()) {
                unlink($spooled);
                continue;
            }

            if (! $this->recordWebmention()) {
                continue;
            }

            unlink($spooled);
        }
    }

    /**
     * Check that the webmention source contains the target URL
     *
     * @return bool True If the source contains the target URL.
 * False If the source does not contain the target URL.
     */
    private function sourceContainsTargetLink(): bool
    {
        $curl_handle = curl_init($this->getSource());
        curl_setopt_array(
            $curl_handle,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_REFERER => $this->getConfig()->getMe(),
                CURLOPT_USERAGENT => "webfoo (webmention; endpoint discovery)",
            ]
        );
        $response = curl_exec($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        if ($status != 200) {
            return false;
        }

        return $response
            && is_string($response)
            && (strpos($response, $this->getTarget()) !== false);
    }

    /**
     * Update the post record with received webmentions
     *
     * @return bool True The webmention has been recorded.
 * False Recording the webmention has failed.
     */
    private function recordWebmention(): bool
    {
        $webmentions = [
            'generic' => [
                'count' => 0,
                'items' => [],
            ],
            'response' => [
                'count' => 0,
                'items' => [
                    'like' => [
                        'count' => 0,
                        'items' => [],
                    ],
                    'bookmark' => [
                        'count' => 0,
                        'items' => [],
                    ],
                    'reply' => [
                        'count' => 0,
                        'items' => [],
                    ],
                ],
            ],
            'repost' => [
                'count' => 0,
                'items' => [],
            ],
        ];

        $webmentions_file = fopen($this->getTargetPostRecordPath() . 'web.mentions', 'c+');

        if (! $webmentions_file) {
            return false;
        }

        if (! flock($webmentions_file, LOCK_EX)) {
            fclose($webmentions_file);

            return false;
        }

        $contents = fread($webmentions_file, filesize($this->getTargetPostRecordPath() . 'web.mentions'));

        if (0 < strlen($contents)) {
            $yaml = yaml_parse($contents);

            if ($yaml) {
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
