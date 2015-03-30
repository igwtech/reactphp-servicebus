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
 * Description of FileProducer
 *
 * @author javier
 */
class FileProducer extends TimerProducer  {
    
    public $filter;
    public $renameExt;
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->filter='//';
        $this->renameExt=false;
    }
    
    public function process(MessageInterface &$msg) {
        try {
            if(!file_exists($this->params['path'])) {
                mkdir($this->params['path'], 0777, true);
            }
            foreach (new \DirectoryIterator($this->params['path']) as $fileInfo) {
                if($fileInfo->isDot()) continue;
                if($fileInfo->isDir()) continue;

                $fullpath = $fileInfo->getPath().DIRECTORY_SEPARATOR.$fileInfo->getFilename();
                if(!preg_match($this->filter, $fileInfo->getFilename())) continue;
                
                $msg->setHeader('Filename', $fileInfo->getFilename());
                $msg->setBody(file_get_contents($fullpath));
                if($this->renameExt) {
                    move_uploaded_file($fullpath, $fullpath.$this->renameExt);
                }else{
                    //Delete
                    unlink($fullpath);
                }
                return;
            }
            $msg=null; // Ntohing found
        }catch(Exception $e) {
            $this->emit('error',[$e]);
        }
    }
    
    public function __destruct() {
        //$this->loop->cancelTimer
    }
    
}
