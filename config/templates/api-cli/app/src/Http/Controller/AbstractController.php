<?php

namespace MyApp\Http\Controller;

use Pop\Application;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;

abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Application object
     * @var ?Application
     */
    protected ?Application $application = null;

    /**
     * Request object
     * @var ?Request
     */
    protected ?Request $request = null;

    /**
     * Response object
     * @var ?Response
     */
    protected ?Response $response = null;

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
    public function application(): Application
    {
        return $this->application;
    }

    /**
     * Get request object
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Get response object
     *
     * @return Response
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * Send response
     *
     * @param  int     $code
     * @param  mixed   $body
     * @param  ?string $message
     * @param  ?array  $headers
     * @return void
     */
    public function send(int $code = 200, mixed $body = null, ?string $message = null, ?array $headers = null): void
    {
        $this->response->setCode($code);

        if ($message !== null) {
            $this->response->setMessage($message);
        }

        $this->response->addHeaders($this->application->config['http_options_headers']);

        $responseBody = (($this->response->getHeader('Content-Type')->getValue() == 'application/json') && ($body  !== null) && ($body != '')) ?
            json_encode($body, JSON_PRETTY_PRINT) : $body;

        $this->response->setBody($responseBody . PHP_EOL . PHP_EOL);
        $this->response->send(null, $headers);
    }

    /**
     * Send options
     *
     * @param  int     $code
     * @param  ?string $message
     * @param  ?array  $headers
     * @return void
     */
    public function sendOptions(int $code = 200, ?string $message = null, ?array $headers = null): void
    {
        $this->send($code, '', $message, $headers);
    }

    /**
     * Send error
     *
     * @param  int     $code
     * @param  ?string $message
     * @return void
     */
    public function error(int $code = 404, ?string $message = null): void
    {
        if ($message === null) {
            $message = Response::getMessageFromCode($code);
        }

        $responseBody = json_encode(['code' => $code, 'message' => $message], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

        $this->response->setCode($code)
            ->setMessage($message)
            ->addHeaders($this->application->config['http_options_headers'])
            ->setBody($responseBody)
            ->sendAndExit();
    }

    /**
     * Send maintenance
     *
     * @param  int     $code
     * @param  ?string $message
     * @return void
     */
    public function maintenance(int $code = 503, ?string $message = null): void
    {
        $this->error($code, $message);
    }

}
