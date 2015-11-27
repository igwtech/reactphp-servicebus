<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Greicodex\ServiceBuz\Processors\Consumers\HttpServer;
/**
 * Description of newPHPClass
 *
 * @author javiermunoz
 */
class HttpServerListener extends \Evenement\EventEmitter implements \React\Http\ServerInterface {
    protected $socket;
    protected $http;
    public function __construct(\React\EventLoop\LoopInterface $loop) {
        \Monolog\Registry::getInstance('main')->addInfo(__CLASS__ . ' Constructed ');
       $this->socket = new \React\Socket\Server($loop);
       $this->http = new \React\Http\Server($this->socket);
    }
    public function __destruct() {
        $this->http->removeAllListeners();
        $this->socket->shutdown();
    }

    public function emit($event, array $arguments = array()) {
        return $this->http->emit($event,$arguments);
    }

    public function listeners($event) {
        return $this->http->listeners($event);
    }

    public function on($event, callable $listener) {
        return $this->http->on($event, $listener);
    }

    public function once($event, callable $listener) {
        return $this->http->once($event, $listener);
    }

    public function removeAllListeners($event = null) {
        return $this->http->removeAllListeners($event);
    }

    public function removeListener($event, callable $listener) {
        return $this->http->removeListener($event, $listener);
    }
    
    public function listen($port,$host='127.0.0.1') {
        return $this->socket->listen($port, $host);
        
    }
    
    public function getPort() {
        return $this->socket->getPort();
    }
    

}
