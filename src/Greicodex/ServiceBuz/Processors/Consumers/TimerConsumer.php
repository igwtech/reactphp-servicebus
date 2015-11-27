<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Consumers;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
/**
/**
 * Description of TimerProducer
 *
 * @author javier
 */
class TimerConsumer extends \Greicodex\ServiceBuz\Processors\BaseProcessor {
    const TYPE_PERIODIC='periodic';
    const TYPE_ONCE='once';
    
    public $type;
    public $delay;
    public $data;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->type=TimerConsumer::TYPE_PERIODIC;
        $this->delay=1.0;
        $this->data='';
    }

    public function configure() {
        parent::configure();
        \Monolog\Registry::getInstance('main')->addDebug("Type:".$this->type.", delay:".$this->delay);
        \Monolog\Registry::getInstance('main')->addDebug(print_r($this->params,true));
        if($this->type == TimerConsumer::TYPE_PERIODIC) {
            $this->loop->addPeriodicTimer($this->delay, function(TimerInterface $t) {
               \Monolog\Registry::getInstance('main')->addDebug('Tick!');
               $msg=new \Greicodex\ServiceBuz\BaseMessage();
               $msg->setHeader('Timestamp', (new \DateTime())->format('c'));
               $msg->setBody($this->data);
               $this->process($msg); 
               if($msg !== null) {
                    $this->emit('message',[$msg]);
               }
            });
        }else{
            $this->loop->addTimer($this->delay, function(TimerInterface $t) {
                \Monolog\Registry::getInstance('main')->addDebug('Tock!');
               $msg=new \Greicodex\ServiceBuz\BaseMessage();
               $msg->setHeader('Timestamp', (new \DateTime())->format('c'));
               $msg->setBody($this->data);
               $this->process($msg); 
               if($msg !== null) {
                    $this->emit('message',[$msg]);
               }
            });
        }
    }

    public function process(MessageInterface &$msg) {
        // DO nothing
    }

}
