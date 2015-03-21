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
use \React\Promise\Deferred;
/**
 * Description of BaseProcessor
 *
 * @author javier
 */
abstract class BaseProcessor implements ProcessorInterface {
    use \Evenement\EventEmitterTrait;
    protected $loop;
    protected $deferred;
    protected $params;
    
    /**
     * 
     * @param LoopInterface $loop
     * @param \Greicodex\ServiceBuz\Processors\callable $canceller
     */
    public function __construct(LoopInterface $loop, callable $canceller = null) {
        $this->deferred=new Deferred($canceller);
        $this->loop=$loop;
    }
    
    /**
     * Configures the Processor: Must be overriden to configure the Processor
     */
    abstract public function configure(array $options);
    
    /**
     * Transform the Message: Must be overriden on derived class
     * @param \Greicodex\ServiceBuz\MessageInterface $msg
     * @return \Greicodex\ServiceBuz\MessageInterface
     */
    abstract public function process(MessageInterface &$msg);


    public function __get($name) {
        if(in_array($name,get_class_methods(self::class))) {
            return array($this,$name);
        }
    }

    public function feed(MessageInterface $msg) {
        $this->emit('processor.input',[$msg]);
        $that=$this;
        $omsg=null;
        $this->loop->nextTick(function($l) use (&$that,&$msg) {
            
            try {
                
                $omsg=$that->process($msg);
                $that->emit('processor.output',[$omsg]);
                $that->deferred->resolve($msg);

            }catch(Exception $ie) {
                $e = new \Exception('Error processing Message', 800041, $ie);
                $e->originalMessage= $msg;
                $e->transformed=$omsg;
                $that->emit('processor.error',[$e]);
                $that->deferred->reject($e);

            }
        });
        
        return $this->promise();
    }

    public function promise() {
        return $this->deferred->promise();
    }

//put your code here
}
