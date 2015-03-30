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
    public $append;
    public $filename;
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->append=false;
        $this->filename='temp';
    }

    public function configure() {
        $this->parseParams();
    }
    
    public function getFilename() {
        return tempnam($this->params['path'], $this->filename);
    }

    public function process(MessageInterface &$msg) {
        //consume message
        var_dump('Process FILE');
        $filename=$this->getFilename();
        var_dump('file:'.$filename);
        
        $msg->addHeader('Filename', $filename);
        $fd=fopen($filename, $this->getMode());
        if(false === $fd) {
            throw new \ErrorException('Unable to open file '.$filename);
        }
        $stream = new \React\Stream\Stream( $fd,$this->loop);

        $data=(string)($msg->getBody());
        
        $stream->on('error',function($e) use(&$msg) {
            $this->emit('error',[$e,$msg]);
        });
        $stream->on('end',function() use(&$msg) {    
            $this->emit('message',[$msg]);
        });

        $stream->write($data);
        $stream->end();
        
        
    }
    
    public function getMode() {
        if($this->append) {
            return 'a+';
        }
        return 'w+';
    }
}
