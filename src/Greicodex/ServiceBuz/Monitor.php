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
            if(file_exists(WEBDIR.$request->getPath())) {
                $response->writeHead(200);
                $response->write(file_get_contents(WEBDIR.$request->getPath()));
            }elseif($request->getPath() ==='/status') {
                $this->status($request, $response);
            }elseif($request->getPath() === '/') {
                $response->writeHead(307,array('Location'=>'/pages/login.html'));
                $response->write('<html><meta http-equiv="refresh" content="0;url=pages/login.html"></html>');
            }else{
                $response->writeHead(404);
                $response->write('Not found');
            }
            $response->end();
        });
        
        $socket->listen(8080);
    }
    
    
    
    public function status(&$request,&$response) {
        $response->writeHead(200, array('Content-Type' => 'application/json'));
        $statuses=array();
        foreach($this->routes as $name=>$route) {
            $statuses[$name]=array('status'=>$route->getStatus());
        }
        $response->write(json_encode($statuses));
    }
}
