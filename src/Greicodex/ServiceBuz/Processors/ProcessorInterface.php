<?php

namespace Greicodex\ServiceBuz\Processors;
use \Evenement\EventEmitterInterface;
use React\Promise\PromisorInterface;
/**
 *
 * @author javier
 */
interface ProcessorInterface extends EventEmitterInterface {
    public function configure();
    public static function FactoryCreate( $uri, \React\EventLoop\LoopInterface $loop);
    
    /**
     * Transform the Message: Must be overriden on derived class
     * Must return inmediately and emit a [message] event when the message has been 
     * transformed with the new output message or an [error] envent
     * @param \Greicodex\ServiceBuz\MessageInterface $msg
     * @return \Greicodex\ServiceBuz\MessageInterface
     */
    public function process(\Greicodex\ServiceBuz\MessageInterface &$msg);
    
    /**
     * Called during Route setup
     * Gives a chance to register Event handlers for the Processor to 
     * receive/generate messages on the chain.
     * Must emit [processor.connect.done] events on succesful chaining or
     * [processor.connect.error] events on errors.
     * @param \Greicodex\ServiceBuz\Processors\ProcessorInterface $nextProc
     * @return \Greicodex\ServiceBuz\Processors\ProcessorInterface 
     */
    public function forwardTo(ProcessorInterface &$nextProc);
}
