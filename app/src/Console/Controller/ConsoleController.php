<?php

namespace TestApp\Console\Controller;

class ConsoleController extends AbstractController
{

    /**
     * Help action
     *
     * @return void
     */
    public function help()
    {
        $this->console->help();
    }

}