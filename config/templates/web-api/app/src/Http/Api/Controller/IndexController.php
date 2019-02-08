<?php

namespace MyApp\Http\Api\Controller;

class IndexController extends AbstractController
{

    /**
     * Index action
     *
     * @return void
     */
    public function index()
    {
        $this->send(200, ['message' => 'Index page']);
    }

}