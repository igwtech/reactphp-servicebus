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
        $this->filename='%tempnam%';
    }

    public function configure() {
        $this->parseParams();
        
    }
    public function getFilename() {
        return tempnam($this->params['path'], 'bus');
    }

    public function process(MessageInterface &$msg) {
        //consume message
        var_dump('Process FILE');
        $filename=$this->getFilename();
        var_dump('file:'.$filename);
        
        $msg->addHeader('Filename', $filename);
        
        $stream = new \React\Stream\Stream( fopen($filename, $this->getMode()),$this->loop);

        $data='HOLA MUNDO';// (string)($msg->getBody());
        
        $stream->on('error',function($e) use(&$msg) {
            var_dump('HERE!!!!HERE!!!!ARRRGGH');

            $this->emit('error',[$e,$msg]);
        });
        $stream->on('end',function() use(&$msg) {    
            //var_dump('HERE!!!!HERE!!!!');            
            //fclose($fd);
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
