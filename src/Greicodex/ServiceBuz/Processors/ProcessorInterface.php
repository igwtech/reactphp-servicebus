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
    //public function chainTo();
    //public function getInputStream();
    //public function getOutputStream();
}
