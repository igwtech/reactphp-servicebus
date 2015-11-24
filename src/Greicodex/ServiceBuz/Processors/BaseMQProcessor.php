<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use Bunny\Async\Client;

/**
 * Description of AMQPProducer
 *
 * @author javier
 */
class BaseMQProcessor extends \Greicodex\ServiceBuz\Processors\BaseProcessor  {
    public $routingKey;
    public $type;
    public $passive;
    public $durable;
    public $auto_delete;
    public $exclusive;
    protected $connection;
    protected $channel;
    protected $vhost;
    
    
    /**
     * Constructor
     * @param LoopInterface $loop
     * @param \Greicodex\ServiceBuz\Processors\callable $canceller
     */
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
    }
    
    public function configure() {
        parent::parseParams();
    }
    
    protected function connectAMQP() {
        $this->vhost=  dirname($this->params['path']);
        
        if($this->connection === null  ) {
            $this->connection = new Client($this->loop,['host'=>$this->params['host'],'port'=>$this->params['port'],'user'=>$this->params['user'],'password'=>$this->params['pass'],$this->vhost]);
        }
        $deferred = new \React\Promise\Deferred();
        
        $this->connection->connect()->then(function () {
            return $this->connection->channel();
        })->then(function (\Bunny\Channel $channel) use(&$deferred) {
            $this->channel = $channel;
            $deferred->resolve($channel);
        });
        return $deferred->promise();
    }
    
    public function __destruct() {
        $this->connection->disconnect()->then(function () {
            $this->loop->stop();
        });
    }
}
