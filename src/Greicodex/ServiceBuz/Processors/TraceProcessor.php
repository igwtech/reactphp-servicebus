<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
/**
 * Description of NullProcessor
 *
 * @author javier
 */
class TraceProcessor extends BaseProcessor {
    private $format;
    protected static $instance_count=0;
    private $instance_id;
    public function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        
    }

    
    public function process(MessageInterface &$msg) {
        $history=@$msg->getHeader('trace') or [];
        $history[]= (string) $this;
        $msg->setHeader('trace',$history );
        return $msg;
    }
    public function configure(array $options) {
        $this->instance_id=TraceProcessor::$instance_count++;
        $this->format = $options['query']['format'];
    }

    public function __toString() {
         return sprintf($this->format,get_class($this),  $this->instance_id);
    }

    

}
