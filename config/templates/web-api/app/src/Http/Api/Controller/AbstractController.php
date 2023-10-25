<?php

namespace MyApp\Http\Api\Controller;

abstract class AbstractController extends \MyApp\Http\Controller\AbstractController
{

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
     * @param  int    $code
     * @param  string $message
     * @return void
     */
    public function error(int $code = 404, ?string $message = null): void
    {
        if ($message === null) {
            $message = \Pop\Http\Server\Response::getMessageFromCode($code);
        }

        $responseBody = json_encode(['code' => $code, 'message' => $message], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

        $this->response->setCode($code)
            ->setMessage($message)
            ->addHeaders($this->application->config['http_options_headers'])
            ->setBody($responseBody)
            ->sendAndExit();
    }

}