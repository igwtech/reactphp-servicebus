<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Protocols;
use PhpAmqpLib\Connection\AMQPStreamConnection;
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
    
    public function getChannel($host='localhost',$port=5672,$user='guest',$pass='guest') {
        if($port==null) $port=5672;
        $hash=md5("$host,$port,$user,$pass");
        $this->connections->rewind();
        $connection=null;
        while($this->connections->valid()) {
            if($this->connections->getInfo() === $hash) {
                $connection=$this->connections->current();
            }
            $this->connections->next();
        }
        if(null===$connection) {
            \Monolog\Registry::getInstance('main')->addNotice("Connecting with RabbitMQ '$host':'$port' as '$user'");
            $connection=new AMQPStreamConnection($host,$port,$user,$pass);
            $this->connections->attach($connection, $hash);
        }
        
        if(!$connection->isConnected()) {
    
            throw new \ErrorException("AMQP Connection could not be established");            
        }

        return $connection->channel();
        //$this->channel->queue_declare($this->queue_name, $this->passive, $this->durable,$this->exclusive, $this->auto_delete);
        
    }
}
