<?php

namespace MyApp\Http\Web\Controller;

use Pop\View\View;

abstract class AbstractController extends \MyApp\Http\Controller\AbstractController
{

    /**
     * View path
     * @var string
     */
    protected string $viewPath = __DIR__ . '/../../../../view';

    /**
     * View object
     * @var ?View
     */
    protected ?View $view = null;

    /**
     * Get view object
     *
     * @return View
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * Determine if the controller has a view
     *
     * @return bool
     */
    public function hasView(): bool
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
    public function redirect(string $url, int $code = 302, string $version = '1.1'): void
    {
        \Pop\Http\Server\Response::redirect($url, $code, $version);
        exit();
    }

    /**
     * Send method
     *
     * @param  int     $code
     * @param  string  $body
     * @param  ?string $message
     * @param  ?array  $headers
     * @return void
     */
    public function send(int $code = 200, mixed $body = null, ?string $message = null, ?array $headers = null): void
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
    protected function prepareView(string $template): void
    {
        $this->view = new View($this->viewPath . '/' . $template);
    }

}