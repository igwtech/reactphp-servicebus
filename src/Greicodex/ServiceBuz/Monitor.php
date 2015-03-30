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
    protected $samples;
    protected static $mimetypes=array(
    '.js'=>'text/javascript',
    '.css'=>'text/css',
    '.json'=>'application/json',
    '.pdf'=>'application/pdf',
    '.jpg'=>'image/jpeg',
    '.html'=>'text/html',
    '.map'=>'application/x-navimap',
    '.woff'=>'application/x-font-woff'
        
    );
    public function __construct($routes, LoopInterface $loop) {
        $this->samples=array();
        $socket = new \React\Socket\Server($loop);
        $this->routes=$routes;
        $http = new \React\Http\Server($socket);
        $http->on('request', function ($request, $response) {
                        
            if(file_exists(WEBDIR.$request->getPath())) {
                $ext= substr(WEBDIR.$request->getPath(),strripos(WEBDIR.$request->getPath(),'.'));
                
                $response->writeHead(200,array('Content-Type'=>Monitor::$mimetypes[$ext] ));
                
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
        $loop->addPeriodicTimer(1.0, array($this,'handleTimer'));
        $socket->listen(8080,'0.0.0.0');
    }
    
    public function getSample() {
        $statuses=array();
        foreach($this->routes as $name=>$route) {
            $statuses[$name]=array('status'=>$route->getStatus());
        }
        return $statuses;
    }
    
    public function status(&$request,&$response) {
        $response->writeHead(200, array('Content-Type' => 'application/json'));
        $last=count($this->samples)-1;
      
        $response->write(json_encode(array('routes'=>$this->samples[$last],'timestamp'=>  microtime(true),'memory'=>  memory_get_usage(),'peak'=>  memory_get_peak_usage())));
    }
    
    public function handleTimer(\React\EventLoop\Timer\TimerInterface $t) {
       $this->samples[]=$this->getSample();
       if(count($this->samples)>1000) {
           array_shift($this->samples);
       }
    }
}
