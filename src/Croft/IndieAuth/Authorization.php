<?php
/**
 * The IndieAuth\Authorization class is herein defined.
 *
 * @package WebFoo\IndieAuth
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\IndieAuth;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;

/**
 * The Authorization class provides methods to service the authorization steps of IndieAuth
 */
class Authorization
{

    use Aether;

    /**
     * Store a local reference to required services
     *
     * @param ConfigInterface   $config   The active configuration.
     * @param RequestInterface  $request  The current request.
     * @param ResponseInterface $response The response.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request, ResponseInterface $response)
    {
        $this->setConfig($config);
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Handle an incoming authorization verification request
     *
     * @param Validation $validation Helper class for validating request parameters.
     *
     * @return boolean True  If the authorization code request is good.
     *                 False If the authorization code request is not good.
     */
    public function codeVerificationRequest(Validation $validation)
    {
        $request = $this->getRequest();

        $this->getResponse()->mergeHeaders('Content-Type: application/json; charset=UTF-8');

        if(!$validation->indieAuthRequestHasParams('authorization code verification')) {
            $this->setResponseBody($validation->getResponseBody());
            return false;
        }

        $client_id = $request->post('client_id');
        $redirect_uri = $request->post('redirect_uri');
        $code = $request->post('code');

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
        if(!file_exists(VAR_ROOT . 'indieauth/auth-' . $filename)) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the authorization code verification request could not be matched to an approved authentication response',
                )
            );
            return false;
        }

        $approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);

        if((now() - 600) > $approval['expires']) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the authorization code verification request matched an approved authentication response that has already expired (10 mins)',
                )
            );
            return false;
        }

        $this->setResponseBody(
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

        if(1 != $approval['used']) {
            $this->setResponseBody(
                array(
                'error' => 'invalid_request',
                'error_description' => 'the authorization code verification request matched an approved authentication response that has already been used',
                )
            );
            return false;
        }

        return true;
    }

}
