<?php

namespace MyApp\Http\Web\Controller;

use Pop\View\View;

abstract class AbstractController extends \MyApp\Http\Controller\AbstractController
{

    /**
     * View path
     * @var string
     */
    protected $viewPath = __DIR__ . '/../../../../view';

    /**
     * View object
     * @var View
     */
    protected $view = null;

    /**
     * Get view object
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Determine if the controller has a view
     *
     * @return bool
     */
    public function hasView()
    {
        return ($this->view !== null);
    }

    /**
     * Redirect method
     *
     * @param  string $url
     * @param  int    $code
     * @param  string $version
     * @return void
     */
    public function redirect($url, $code = 302, $version = '1.1')
    {
        \Pop\Http\Server\Response::redirect($url, $code, $version);
        exit();
    }

    /**
     * Send method
     *
     * @param  int    $code
     * @param  string $body
     * @param  string $message
     * @param  array  $headers
     * @return void
     */
    public function send($code = 200, $body = null, $message = null, array $headers = null)
    {
        if (($body === null) && ($this->view !== null)) {
            $body = $this->view->render();
        }

        if ($message !== null) {
            $this->response->setMessage($message);
        }

        $this->response->setCode($code);
        $this->response->setBody($body . PHP_EOL . PHP_EOL);
        $this->response->send(null, $headers);
    }

    /**
     * Prepare view
     *
     * @param  string $template
     * @return void
     */
    protected function prepareView($template)
    {
        $this->view = new View($this->viewPath . '/' . $template);
    }

}