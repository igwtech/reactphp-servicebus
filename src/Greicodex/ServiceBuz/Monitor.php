<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz;
use React\EventLoop\LoopInterface;
/**
 * Description of Monitor
 *
 * @author javier
 */
class Monitor {
    protected $routes;
    public function __construct($routes, LoopInterface $loop) {
        $socket = new \React\Socket\Server($loop);
        $this->routes=$routes;
        $http = new \React\Http\Server($socket);
        $http->on('request', function ($request, $response) {
            $response->writeHead(200, array('Content-Type' => 'text/plain'));
            foreach($this->routes as $route) {
                $response->write($route->getStatus());
            }
            $response->end();
        });

        $socket->listen(8080);
    }
}
