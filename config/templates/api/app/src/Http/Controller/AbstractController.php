<?php

namespace MyApp\Http\Controller;

use Pop\Application;
use Pop\Http\Request;
use Pop\Http\Response;

abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Application object
     * @var Application
     */
    protected $application = null;

    /**
     * Request object
     * @var Request
     */
    protected $request = null;

    /**
     * Response object
     * @var Response
     */
    protected $response = null;

    /**
     * Constructor for the controller
     *
     * @param  Application $application
     * @param  Request     $request
     * @param  Response    $response
     */
    public function __construct(Application $application, Request $request, Response $response)
    {
        $this->application = $application;
        $this->request     = $request;
        $this->response    = $response;
    }

    /**
     * Get application object
     *
     * @return Application
     */
    public function application()
    {
        return $this->application;
    }

    /**
     * Get request object
     *
     * @return Request
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Get response object
     *
     * @return Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Send response
     *
     * @param  int    $code
     * @param  string $body
     * @param  string $message
     * @param  array  $headers
     * @return void
     */
    public function send($code = 200, $body = null, $message = null, array $headers = null)
    {
        $this->response->setCode($code);

        if (null !== $message) {
            $this->response->setMessage($message);
        }

        $this->response->setHeaders($this->application->config['http_options_headers']);

        $responseBody = (($this->response->getHeader('Content-Type') == 'application/json') && (null !== $body) && ($body != '')) ?
            json_encode($body, JSON_PRETTY_PRINT) : $body;

        $this->response->setBody($responseBody . PHP_EOL . PHP_EOL);
        $this->response->send(null, $headers);
    }

    /**
     * Send options
     *
     * @param  int    $code
     * @param  string $message
     * @param  array  $headers
     * @return void
     */
    public function sendOptions($code = 200, $message = null, array $headers = null)
    {
        $this->send($code, '', $message, $headers);
    }

    /**
     * Send error
     *
     * @param  int    $code
     * @param  string $message
     * @return void
     */
    public function error($code = 404, $message = null)
    {
        if (null === $message) {
            $message = Response::getMessageFromCode($code);
        }

        $responseBody = json_encode(['code' => $code, 'message' => $message], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

        $this->response->setCode($code)
            ->setMessage($message)
            ->setHeaders($this->application->config['http_options_headers'])
            ->setBody($responseBody)
            ->sendAndExit();
    }

}