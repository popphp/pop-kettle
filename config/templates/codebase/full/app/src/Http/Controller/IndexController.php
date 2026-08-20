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
        if ($this->request->acceptsHtml()) {
            $this->prepareView('index.phtml');
            $this->view->title = 'Welcome';
            $this->send();
        } else {
            $this->sendJson(200, ['message' => 'Index page']);
        }
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
        if ($this->request->acceptsHtml()) {
            $this->prepareView('error.phtml');
            $this->view->title = $code . ' ' . ($message ?? \Pop\Http\Server\Response::getMessageFromCode($code));
            $this->send($code);
        } else {
            parent::error($code, $message);
        }
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
        if ($this->request->acceptsHtml()) {
            $this->prepareView('maintenance.phtml');
            $this->view->title = 'Website is Down';
            $this->send($code);
        } else {
            parent::error($code, $message);
        }
    }

}
