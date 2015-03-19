<?php

namespace Greicodex\ServiceBuz\Processors;
use \Evenement\EventEmitterInterface;
use React\Promise\PromisorInterface;
/**
 *
 * @author javier
 */
interface ProcessorInterface extends EventEmitterInterface, PromisorInterface {
    public function configure();
    public function feed(\Greicodex\ServiceBuz\MessageInterface $msg);
    public static function FactoryCreate($uri, \React\EventLoop\LoopInterface $loop);
    //public function attach();
}
