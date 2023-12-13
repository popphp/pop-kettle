<?php

namespace MyApp\Http\Web\Controller;

class IndexController extends AbstractController
{

    /**
     * Index action
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('index.phtml');
        $this->view->title = 'Index';
        $this->send();
    }

    /**
     * Error action
     *
     * @return void
     */
    public function error()
    {
        $this->prepareView('error.phtml');
        $this->view->title = 'Error';
        $this->send(404);
    }

    /**
     * Maintenance action
     *
     * @return void
     */
    public function maintenance(): void
    {
        $this->prepareView('maintenance.phtml');
        $this->view->title = 'Website is Down';
        $this->send(503);
    }

}
