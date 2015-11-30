<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Consumers;

/**
 * Description of FtpConsumer
 *
 * @author javier
 */
class FtpConsumer extends TimerConsumer  {
    
    public $filter;
    public $renameExt;
    public $append;
    public $filename;
    public $passive;
    public $ftpMode;
    
    protected $stream;
    protected $ftp_stream;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->filter='//';
        $this->renameExt=false;
        $this->ftpMode=FTP_BINARY;
        $this->passive=true;
    }
    
    public function process(MessageInterface &$msg) {
        try {
            \Monolog\Registry::getInstance('main')->addNotice('FTP CONNECT :'.$this->params['host'].':'.$this->params['port']);
            $this->ftp_stream=ftp_connect($this->params['host'],$this->params['port']);
            if(false === $this->ftp_stream) {
                throw new Exception("Unable to connect to Server");
            }
            \Monolog\Registry::getInstance('main')->addNotice('FTP LOGIN :'.$this->params['user']);
            $ret=ftp_login($this->ftp_stream, $this->params['user'], $this->params['pass']);
            if(false === $ret) {
                throw new Exception("Unable to ftp_login to Server");
            }
            //ftp_alloc($ftp_stream, $filesize);
            \Monolog\Registry::getInstance('main')->addNotice('FTP CHDIR :'.$this->params['path']);
            $ret=ftp_chdir($this->ftp_stream, $this->params['path']);
            if(false === $ret) {
                throw new Exception("Unable to ftp_chdir to Server");
            }
            \Monolog\Registry::getInstance('main')->addNotice('FTP PASV :'.$this->passive);
            $ret=ftp_pasv($this->ftp_stream, $this->passive);
            if(false === $ret) {
                throw new Exception("Unable to ftp_pasv to Server");
            }
            
            
            $contents = ftp_nlist($this->ftp_stream, "."); 
            foreach ($contents as $file) {

                if ($file == '.' || $file == '..') {
                    continue; 
                }
               
                if (@ftp_chdir($this->ftp_stream, $file)) { 
                    ftp_chdir ($this->ftp_stream, ".."); 
                    //ftp_sync ($file); 
                }else {
                    if(!preg_match($this->filter, $file)) continue;
                    $this->stream = fopen('php://memory','w+');
                    ftp_fget($this->ftp_stream, $this->stream,$file, $this->ftpMode); 
                    rewind($this->stream);
                    $msg->setBody(stream_get_contents($this->stream));
                    fclose($this->stream);
                    $msg->setHeader('Filename', $file);
                    
                    if($this->renameExt) {
                        ftp_rename($this->ftp_stream, $file, $file .$this->renameExt );
                    }else{
                        ftp_delete($this->ftp_stream, $file);
                    }
                    return;
                }
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