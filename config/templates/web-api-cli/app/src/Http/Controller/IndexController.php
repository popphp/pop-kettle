<?php

namespace MyApp\Http\Controller;

use Pop\View\View;

class IndexController extends AbstractController
{

    /**
     * Error action
     *
     * @return void
     */
    public function error()
    {
        $response = ['code' => 404, 'message' => 'Not Found'];

        if (stripos($this->request->getHeader('Accept')->getValue(), 'text/html') !== false) {
            $view        = new View(__DIR__ . '/../../../view/error.phtml', $response);
            $view->title = $response['code'] . ' ' .$response['message'];
            $this->response->addHeader('Content-Type', 'text/html');
            $this->response->setBody($view->render());
        } else {
            $this->response->addHeaders($this->application->config['http_options_headers']);
            $this->response->setBody(json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL);
        }

        $this->response->send(404);
        exit();
    }

}