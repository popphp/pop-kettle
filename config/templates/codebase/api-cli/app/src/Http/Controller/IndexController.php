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
        $this->sendJson(200, ['message' => 'Index page']);
    }

}
