<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
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
class TimerProducer extends \Greicodex\ServiceBuz\Processors\BaseProcessor {
    const TYPE_PERIODIC='periodic';
    const TYPE_ONCE='once';
    
    public $type;
    public $delay;
    public $data;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->type=TimerProducer::TYPE_PERIODIC;
        $this->delay=1.0;
        $this->data='';
    }

    public function configure() {
        $this->parseParams();
        //var_dump(array($this->type,$this->delay));
        //var_dump($this->params);
        if($this->type == TimerProducer::TYPE_PERIODIC) {
            $this->loop->addPeriodicTimer($this->delay, function(TimerInterface $t) {
                var_dump('Tick!');
               $msg=new \Greicodex\ServiceBuz\BaseMessage();
               $msg->setHeader('Timestamp',new \DateTime());
               $msg->setBody($this->data);
               $this->process($msg); 
               $this->emit('message',[$msg]);
            });
        }else{
            $this->loop->addTimer($this->delay, function(TimerInterface $t) {
                var_dump('Tock!');
               $msg=new \Greicodex\ServiceBuz\BaseMessage();
               $msg->setHeader('Timestamp',new \DateTime());
               $msg->setBody($this->data);
               $this->process($msg); 
               $this->emit('message',[$msg]);
            });
        }
    }

    
}
