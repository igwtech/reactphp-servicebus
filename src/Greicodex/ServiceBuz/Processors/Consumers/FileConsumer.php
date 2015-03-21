<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Consumers;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
/**
 * Description of FileConsumer
 *
 * @author javier
 */
class FileConsumer extends BaseProcessor  {
    /**
     * Input Stream
     * @var React\Stream\Stream 
     */
    protected $source;
    public function configure(array $options) {
        $this->source = new Stream(fopen($options['path'], 'r'), $this->loop);
    }

    public function process(MessageInterface &$msg) {
       return $msg;
    }
    
    public function feed() {
        
        $that=$this;
        $omsg=null;
        $this->source->on('data',function($data) use (&$that) {
            
            try {
                $msg = new \Greicodex\ServiceBuz\BaseMessage();
                $msg->setBody($data);
                $omsg=$that->process($msg);
                $that->emit('processor.output',[$omsg]);
                $that->deferred->resolve($msg);

            }catch(Exception $ie) {
                $e = new \Exception('Error processing Message', 800041, $ie);
                $e->transformed=$omsg;
                $that->emit('processor.error',[$e]);
                $that->deferred->reject($e);

            }
        });
        
        return $this->promise();
    }
}
