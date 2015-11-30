<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
/**
 * Description of FileConsumer
 *
 * @author javier
 */
class FileProducer extends BaseProcessor  {
    public $append;
    public $filename;
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->append=false;
        $this->filename='temp';
    }
    
    public function getFilename(MessageInterface $msg) {
        $output=  preg_replace('/{body}/', $msg->getBody(), $this->filename);
        $output=  preg_replace('/{headers}/',print_r($msg->getHeaders(),true), $output);
        $output=  preg_replace_callback('/{header\[([^\]]+)\]}/',function($matches) use (&$msg) {
            return  $msg->getHeader($matches[1]);
        }, $output);
        $output=  preg_replace_callback('/{date(\([^\)]+\))}/',function($matches) { return date($matches[1]); }, $output);
        $output=  preg_replace('/{batchid}/',$msg->getId(), $output);
        return tempnam($this->params['path'], $output);
    }

    public function process(MessageInterface &$msg) {
        //consume message
        \Monolog\Registry::getInstance('main')->addNotice('Process FILE');
        $filename=$this->getFilename($msg);
        \Monolog\Registry::getInstance('main')->addNotice('file:'.$filename);
        
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
