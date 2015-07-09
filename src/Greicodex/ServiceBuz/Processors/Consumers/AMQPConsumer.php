<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Consumers;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use React\EventLoop\LoopInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Description of AMQPConsumer
 *
 * @author javier
 */
class AMQPConsumer extends BaseProcessor  {
    public $routingKey;
    public $type;
    public $passive;
    public $durable;
    public $auto_delete;
    public $exclusive;
    protected $connection;
    protected $channel;
    protected $exchange_name;
    
    
    /**
     * Constructor
     * @param LoopInterface $loop
     * @param \Greicodex\ServiceBuz\Processors\callable $canceller
     */
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
    }
    
    public function process(MessageInterface &$msg) {
        $amqMsg = new AMQPMessage();
        $amqMsg->setBody($msg->getBody());
        
        foreach($msg->getHeaders() as $name=>$value) {
            if($amqMsg->has($name)) {
                $amqMsg->set($name, $value);
            }
        }
        $amqMsg->set('correlation_id',$msg->getId());
        $amqMsg->set('content_type',$msg->getType());
        if($this->channel===null) {
            $this->connectAMQP();
        }
        \Monolog\Registry::getInstance('main')->addNotice('New msg Sent to RabbitMQ:'.$amqMsg->get('correlation_id') );
        $this->channel->basic_publish($amqMsg, $this->exchange_name, $this->routingKey);
        
        $msg=null;
        return $msg;
    }
    
    protected function connectAMQP() {
        
        $this->params['host']=(isset($this->params['host']))?$this->params['host']:'localhost';
        $this->params['port']=(isset($this->params['port']))?$this->params['port']:5672;
        $this->params['user']=(isset($this->params['user']))?$this->params['user']:'guest';
        $this->params['pass']=(isset($this->params['pass']))?$this->params['pass']:'guest';
        $this->exchange_name=  ltrim($this->params['path'], '/');
        \Monolog\Registry::getInstance('main')->addNotice('Connecting with RabbitMQ '.$this->params['host'].':'.$this->params['port'].' as '.$this->params['user']);
        $this->connection=new AMQPStreamConnection($this->params['host'],$this->params['port'],$this->params['user'],$this->params['pass']);
        if(!$this->connection->isConnected()) {
            throw new \ErrorException("AMQP Connection could not be established");            
        }
        $this->channel=$this->connection->channel();
        
        //$this->exchange=$this->channel->exchange_declare($this->exchange_name,$this->type, $this->passive, $this->durable, $this->auto_delete);
    }


    public function forwardTo(ProcessorInterface &$nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $this->connectAMQP();
            
            $nextProc->emit('processor.connect.done',[$nextProc,$this]);
            
        }catch(Exception $ie) {
            $e = new \Exception('Error connecting', 800041, $ie);
            $this->emit('processor.connect.error',[$e]);
        }
        
        return $nextProc;
    }
    
    public function __destruct() {
        if($this->channel!==null) {
            $this->channel->close();
        }
        if($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
    
}
