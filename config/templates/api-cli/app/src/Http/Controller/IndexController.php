<?php

namespace MyApp\Http\Controller;

class IndexController extends AbstractController
{

    public function index(): void
    {
        $this->send(200, ['message' => 'Index page']);
    }

}