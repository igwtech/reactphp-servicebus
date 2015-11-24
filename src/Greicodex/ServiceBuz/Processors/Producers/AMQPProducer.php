<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\Processors\BaseMQProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use Bunny\Async\Client;
use Bunny\Protocol\MethodBasicReturnFrame;

/**
 * Description of AMQPProducer
 *
 * @author javier
 */
class AMQPProducer extends BaseMQProcessor  {
    protected $queue_name;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
    }
    
    public function process(MessageInterface &$msg) {
        $this->channel->wait(null,true);
        $msg=null;
        return $msg;
    }

    protected function connectAMQP() {
        $this->queue_name= basename($this->params['path']);
        return parent::connectAMQP();
    }

    public function forwardTo(ProcessorInterface &$nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $this->connectAMQP()->then(function (\Bunny\Channel $channel) use (&$nextProc) {
                
                return \React\Promise\all([
                     $this->channel->qos(0, 1000),
                     //$this->channel->queueDeclare($this->queue_name,false,true),
                     $this->channel->consume(function (\Bunny\Message $amqMsg, \Bunny\Channel $channel) use (&$nextProc) {
                        \Monolog\Registry::getInstance('main')->addNotice('New msg Received from RabbitMQ '.$amqMsg->deliveryTag);
                        
                        $msg=new \Greicodex\ServiceBuz\BaseMessage();
                        $msg->setBody($amqMsg->content);
                        $msg->setHeaders($amqMsg->headers);
                        \Monolog\Registry::getInstance('main')->addDebug(print_r($msg,true));
                        try {
                            $nextProc->process($msg);
                            $this->channel->ack($amqMsg);
                        }catch(\Exception $e) {
                            $this->channel->nack($amqMsg);
                        }

                     }, $this->queue_name),
                 ]);
            }); 

            $nextProc->emit('processor.connect.done',[$nextProc,$this]);
            
        }catch(Exception $ie) {
            $e = new \Exception('Error connecting', 800041, $ie);
            $this->emit('processor.connect.error',[$e]);
        }
        
        return $nextProc;
    }

}
