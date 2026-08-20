<?php

namespace MyApp\Http\Controller;

class IndexController extends AbstractController
{

    /**
     * Index action
     *
     * @return void
     */
    public function index(): void
    {
        $this->prepareView('index.phtml');
        $this->view->title = 'Welcome';
        $this->send();
    }

    /**
     * Error action
     *
     * @param  int     $code
     * @param  ?string $message
     * @return void
     */
    public function error(int $code = 404, ?string $message = null): void
    {
        $this->prepareView('error.phtml');
        $this->view->title = $code . ' ' . ($message ?? \Pop\Http\Server\Response::getMessageFromCode($code));
        $this->send($code);
    }

    /**
     * Maintenance action
     *
     * @param  int     $code
     * @param  ?string $message
     * @return void
     */
    public function maintenance(int $code = 503, ?string $message = null): void
    {
        $this->prepareView('maintenance.phtml');
        $this->view->title = 'Website is Down';
        $this->send($code);
    }

}
