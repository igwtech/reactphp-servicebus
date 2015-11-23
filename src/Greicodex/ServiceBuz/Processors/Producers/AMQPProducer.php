<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use Bunny\Async\Client;
use Bunny\Protocol\MethodBasicReturnFrame;

/**
 * Description of AMQPProducer
 *
 * @author javier
 */
class AMQPProducer extends \Greicodex\ServiceBuz\Processors\BaseProcessor  {
    public $routingKey;
    public $type;
    public $passive;
    public $durable;
    public $auto_delete;
    public $exclusive;
    protected $connection;
    protected $channel;
    protected $queue_name;
    protected $vhost;
    
    
    /**
     * Constructor
     * @param LoopInterface $loop
     * @param \Greicodex\ServiceBuz\Processors\callable $canceller
     */
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
        $this->vhost=  dirname($this->params['path']);
        
        $this->connection = new Client($this->loop,['host'=>$this->params['host'],'port'=>$this->params['port'],'user'=>$this->params['user'],'password'=>$this->params['pass'],$this->vhost]);
        
    }
    public function forwardTo(ProcessorInterface &$nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $this->connectAMQP();
            $this->connection->connect()->then(function () {
                return $this->connection->channel();
            })->then(function (\Bunny\Channel $channel) {
                $this->channel = $channel;
                return Promise\all([
                     $this->channel->qos(0, 1000),
                     $this->queueDeclare($this->queue_name),
                     $this->consume(function (\Bunny\Message $amqMsg, Channel $channel) use ( &$nextProc) {
                         \Monolog\Registry::getInstance('main')->addNotice('New msg Received from RabbitMQ'.$amqMsg->deliveryTag);

                         $msg=new \Greicodex\ServiceBuz\BaseMessage();
                         $msg->setBody($amqMsg->content);
                         $msg->setHeaders($amqMsg->headers);
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
    public function __destruct() {
        $this->connection->disconnect()->then(function () {
            $this->loop->stop();
        });
    }
}
