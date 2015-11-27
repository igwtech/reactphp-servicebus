<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use Greicodex\ServiceBuz\Processors\BaseMQProcessor;
use Greicodex\ServiceBuz\Protocols\AMQPool;
use React\EventLoop\LoopInterface;
use \Bunny\Message;

/**
 * Description of AMQPConsumer
 *
 * @author javier
 */
class MQProducer extends BaseMQProcessor  {
    protected $exchange_name;
    
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
    }
    
    protected function connectAMQP() {
        $this->exchange_name= basename($this->params['path']);
        return parent::connectAMQP();
    }
    
    
    public function process(MessageInterface &$msg) {
        \Monolog\Registry::getInstance('main')->addNotice('Processing Msg for RabbitMQ');
        $headers=array();    
        foreach($msg->getHeaders() as $name=>$value) {
            $headers[$name]=$value;
        }
        $headers['correlation_id']=$msg->getId();
        $headers['content_type']=$msg->getType();
        if($this->channel === null) {
            $this->connectAMQP()->then(function() use (&$msg,$headers) { 
                \Monolog\Registry::getInstance('main')->addNotice('New msg Sent to RabbitMQ Exch:['.  $this->exchange_name . '] Route:['.$this->routingKey.']');
                $this->channel->publish($msg->getBody(),$headers, $this->exchange_name, $this->routingKey);
                $msg=null;
            });
        }else{
            \Monolog\Registry::getInstance('main')->addNotice('New msg Sent to RabbitMQ Exch:['.  $this->exchange_name . '] Route:['.$this->routingKey.']');
            $this->channel->publish($msg->getBody(),$headers, $this->exchange_name, $this->routingKey);
            $msg=null;
        }
    
        return $msg;        
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
   
    
}
