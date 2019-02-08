<?php

namespace MyApp\Http\Api\Controller;

abstract class AbstractController extends \MyApp\Http\Controller\AbstractController
{

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
            $message = \Pop\Http\Response::getMessageFromCode($code);
        }

        $responseBody = json_encode(['code' => $code, 'message' => $message], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

        $this->response->setCode($code)
            ->setMessage($message)
            ->setHeaders($this->application->config['http_options_headers'])
            ->setBody($responseBody)
            ->sendAndExit();
    }

}