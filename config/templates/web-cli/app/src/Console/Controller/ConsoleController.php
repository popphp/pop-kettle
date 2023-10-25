<?php

namespace MyApp\Console\Controller;

class ConsoleController extends AbstractController
{

    /**
     * Help action
     *
     * @return void
     */
    public function help(): void
    {
        $this->console->help();
    }

}