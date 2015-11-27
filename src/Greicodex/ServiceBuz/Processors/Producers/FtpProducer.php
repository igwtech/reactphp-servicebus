<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Producers;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use React\EventLoop\LoopInterface;
use Greicodex\ServiceBuz\MessageInterface;
/**
 * Description of FtpProducer
 *
 * @author javier
 */
class FtpProducer extends BaseProcessor  {
    public $append;
    public $filename;
    public $passive;
    public $ftpMode;
    
    protected $stream;
    protected $ftp_stream;
    protected $uploadStatus;
    protected $transferFilename;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->append=false;
        $this->filename='temp';
        $this->ftpMode=FTP_BINARY;
        $this->passive=true;
    }
    
    public function getFilename(MessageInterface $msg) {
        $output=  preg_replace('/{body}/', $msg->getBody(), $this->filename);
        $output=  preg_replace('/{headers}/',print_r($msg->getHeaders(),true), $output);
        $output=  preg_replace_callback('/{header\[([^\]]+)\]}/',function($matches) use (&$msg) {
            return  $msg->getHeader($matches[1]);
        }, $output);
        $output=  preg_replace_callback('/{date(\([^\)]+\))}/',function($matches) { return date($matches[1]); }, $output);
        $output=  preg_replace('/{batchid}/',$msg->getId(), $output);
        return $output;
    }

    public function process(MessageInterface &$msg) {
        //consume message
        \Monolog\Registry::getInstance('main')->addNotice('Process FILE');
        $this->transferFilename=$this->getFilename($msg);
        \Monolog\Registry::getInstance('main')->addNotice('Temp file:'.$this->transferFilename);
        
        $msg->addHeader('Filename', $this->transferFilename);
        $fd=fopen('/tmp/'.$this->transferFilename, $this->getMode());
        if(false === $fd) {
            throw new \ErrorException('Unable to open file '.$this->transferFilename);
        }
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
        $this->stream = new \React\Stream\Stream( $fd,$this->loop);
        
        $data=(string)($msg->getBody());
        
        $this->stream->on('error',function($e) use(&$msg) {
            $this->emit('error',[$e,$msg]);
        });
        $this->stream->on('end',function() {  
            \Monolog\Registry::getInstance('main')->addNotice('Temp File Written');
            rewind($this->stream->stream);
            \Monolog\Registry::getInstance('main')->addNotice('FTP PUT :'.$this->transferFilename.'.tmp');
            $this->uploadStatus=  ftp_nb_fput($this->ftp_stream,$this->transferFilename.'.tmp', $this->stream->stream, $this->ftpMode);    
            if(false === $this->uploadStatus) {
                throw new Exception("Unable to ftp_nb_fput to Server");
            }
            $this->loop->futureTick(function() { $this->doUpload(); });
        });
        \Monolog\Registry::getInstance('main')->addNotice('Temp File start Write');
        $this->stream->write($data);
        $this->stream->end();
    }
    
    
    protected function doUpload() {
        
        if(FTP_MOREDATA === $this->uploadStatus) {
            $this->uploadStatus = ftp_nb_continue($this->ftp_stream);
            $this->loop->futureTick(function() { $this->doUpload(); });
        }else{
            \Monolog\Registry::getInstance('main')->addNotice('FTP PUT done');
            if (FTP_FINISHED === $this->uploadStatus) {
                //$this->emit('message',[]);
                // Disposition
                \Monolog\Registry::getInstance('main')->addNotice('FTP RENAME :'.$this->transferFilename.'.tmp to '. $this->transferFilename);
                ftp_rename($this->ftp_stream, $this->transferFilename.'.tmp', $this->transferFilename);
            }else{
                $this->emit('error',[]);
            }
            \Monolog\Registry::getInstance('main')->addNotice('FTP CLOSE');
            $this->stream->close();
            ftp_close($this->ftp_stream);
        }
    }
    
    
    public function getMode() {
        if($this->append) {
            return 'a+';
        }
        return 'w+';
    }
}


