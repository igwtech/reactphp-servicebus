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
/**
 * Description of FileConsumer
 *
 * @author javier
 */
class FileConsumer extends BaseProcessor  {
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
    }

    public function configure() {
        
    }
    public function getFilename() {
        return tempnam('/tmp', 'bus');
    }

    public function process(MessageInterface &$msg) {
        //consume message
        $filename=$this->getFilename();
        $fd=  fopen($filename, $this->getMode());
        if(false === $fd) {
            $this->emit('error',[new ErrorException('Unable to open file:'.$filename)]);
            return;
        }
        $msg->addHeader('Filename', $filename);
        $this->stream = new \React\Stream\Stream($fd,$this->loop);
        $this->stream->resume();
        $this->stream->write((string)$msg->getBody());
        $this->stream->end();
        var_dump($msg);
        $this->stream->on('drain',function($stream) use(&$fd) {
            
            $stream->close();
            $stream=null;
            fclose($fd);
            
        });
    }
    public function getMode() {
        return 'w+';
    }
}
