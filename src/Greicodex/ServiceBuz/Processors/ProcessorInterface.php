<?php

namespace Greicodex\ServiceBuz\Processors;
use \Evenement\EventEmitterInterface;
use React\Promise\PromisorInterface;
/**
 *
 * @author javier
 */
interface ProcessorInterface extends EventEmitterInterface, PromisorInterface {
    public function configure(array $options);
    public function feed(\Greicodex\ServiceBuz\MessageInterface $msg);

    //public function attach();
}
