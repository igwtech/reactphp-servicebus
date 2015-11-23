<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Protocols;
use Bunny\Async\Client;
use Bunny\Protocol\MethodBasicReturnFrame;
/**
 * Description of AMQPool
 * Singleton class to manage connections to MQ servers
 * @author javier
 */
class AMQPool {
    private static $instance;
    protected $connections;
    
    public static function getInstance() {
        if(self::$instance ===null) {
            self::$instance=new AMQPool();
        }
        return self::$instance;
    }

    protected function __construct() {
        $this->connections= new \SplObjectStorage();
    }
    
    /**
     * Creates a channel from a new or stablished connection
     * @param \React\EventLoop\LoopInterface $loop
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $vhost
     * @return Promise
     */
    public function getChannel(\React\EventLoop\LoopInterface $loop, $host='localhost',$port=5672,$user='guest',$pass='guest',$vhost='/') {
        if($port==null) $port=5672;
        $hash=md5("$host,$port,$user,$pass");
         
        $this->connections->rewind();
        $connection=$this->getConnection()->then(function() use ($connection) {
            if(!$connection->isConnected()) {
            throw new \ErrorException("AMQP Connection could not be established");            
        }
        return $connection->channel();
        });
        
        
    }
    
    /**
     * 
     * @param \React\EventLoop\LoopInterface $loop
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $vhost
     * @return Promise
     */
    public function getConnection(\React\EventLoop\LoopInterface $loop, $host='localhost',$port=5672,$user='guest',$pass='guest',$vhost='/') {
        while($this->connections->valid()) { // Client instances
            if($this->connections->getInfo() === $hash) {
                $connection=$this->connections->current();
            }
            $this->connections->next();
        }
        if(null===$connection) {
            \Monolog\Registry::getInstance('main')->addNotice("Connecting with RabbitMQ '$host':'$port' $vhost as '$user'");
            $connection=new Client($loop,['host'=>$host,'port'=> $port,'user'=>$user,'password'=>$pass,'vhost'=>$vhost]);
            
            $this->connections->attach($connection, $hash);
            return $connection->connect()->then(function() use ($connection) {
                
                return $connection->channel();
            });
        }
    }
}
