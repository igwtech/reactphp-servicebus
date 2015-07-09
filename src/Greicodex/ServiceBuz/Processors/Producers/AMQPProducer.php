<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\Producers\TimerProducer;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Description of AMQPProducer
 *
 * @author javier
 */
class AMQPProducer extends  TimerProducer  {
    public $routingKey;
    public $type;
    public $passive;
    public $durable;
    public $auto_delete;
    public $exclusive;
    protected $connection;
    protected $channel;
    protected $queue_name;
    
    
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
        
        $this->params['host']=(isset($this->params['host']))?$this->params['host']:'localhost';
        $this->params['port']=(isset($this->params['port']))?$this->params['port']:5672;
        $this->params['user']=(isset($this->params['user']))?$this->params['user']:'guest';
        $this->params['pass']=(isset($this->params['pass']))?$this->params['pass']:'guest';
        $this->queue_name=  ltrim($this->params['path'], '/');
        \Monolog\Registry::getInstance('main')->addNotice('Connecting with RabbitMQ '.$this->params['host'].':'.$this->params['port'].' as '.$this->params['user']);
        $this->connection=new AMQPStreamConnection($this->params['host'],$this->params['port'],$this->params['user'],$this->params['pass']);
        if(!$this->connection->isConnected()) {
            throw new \ErrorException("AMQP Connection could not be established");            
        }
        $this->channel=$this->connection->channel();
        //$this->channel->queue_declare($this->queue_name, $this->passive, $this->durable,$this->exclusive, $this->auto_delete);
        
    }
    public function forwardTo(ProcessorInterface &$nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $this->connectAMQP();
            $this->channel->basic_consume($this->queue_name, $this->routingKey, false, false, false, false, function(AMQPMessage $amqMsg) use (&$nextProc) {
                \Monolog\Registry::getInstance('main')->addNotice('New msg Received from RabbitMQ:'.$amqMsg->delivery_info['delivery_tag']);
                $msg=new \Greicodex\ServiceBuz\BaseMessage();
                $msg->setBody($amqMsg->body);
                $msg->setHeaders($amqMsg->get_properties());
                try {
                    $nextProc->process($msg);
                    $amqMsg->delivery_info['channel']->basic_ack($amqMsg->delivery_info['delivery_tag']);
                }catch(\Exception $e) {
                    $amqMsg->delivery_info['channel']->basic_ack($amqMsg->delivery_info['delivery_tag']);
                }
            });
        
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
