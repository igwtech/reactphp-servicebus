<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\Stream\Stream;

/**
 * Description of FileProducer
 *
 * @author javier
 */
class FileProducer extends BaseProcessor  {
    /**
     * Output stream
     * @var React\Stream\Stream 
     */
    protected $destination;
    public function configure(array $options) {
        $this->destination = new Stream(fopen($options['path'], 'w'), $this->loop);
    }

    public function process(MessageInterface &$msg) {
         $this->destination->write($msg->getBody());
    }
    
    public function __destruct() {
        $this->destination->end();
    }
    
}
