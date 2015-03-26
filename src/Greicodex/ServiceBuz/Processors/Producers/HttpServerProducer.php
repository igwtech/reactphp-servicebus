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
use React\EventLoop\Timer\TimerInterface;
/**
 * Description of newPHPClass
 *
 * @author javiermunoz
 */
class HttpServerProducer  extends BaseProcessor {
    protected static $httpListeners;
    protected static $processorMap;
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        if(null === HttpServerProducer::$httpListeners){
            HttpServerProducer::$httpListeners=array();
        }
        if(null === HttpServerProducer::$processorMap){
            HttpServerProducer::$processorMap=array();
        }
    }
    public function configure() {
        parent::parseParams();
        
        $port = (isset($this->params['port']))?$this->params['port']:80;
        if(!isset(self::$httpListeners[$port])) {
            $http = self::$httpListeners[$port]=new HttpServer\HttpServerListener($this->loop);
            $http->listen($port);
            var_dump("Listening on port $port");
        }
    }
    
    public function forwardTo(\Greicodex\ServiceBuz\Processors\ProcessorInterface $nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $port = (isset($this->params['port']))?$this->params['port']:80;
            $http=self::$httpListeners[$port];
            $http->on('request', function ($request, $response) use(&$nextProc) {
                if(isset(self::$processorMap[$request->getPath()])){
                    $processor=self::$processorMap[$request->getPath()];
                    $this->_dispatchRequest($request,$response,$processor);
                }else{
                    //$response->writeHead(404, array('Content-Type' => 'text/plain'));
                    //$response->write('Not found');
                    $response->end();
                }
                
            });
            self::$processorMap[$this->params['path']]=$nextProc;
            $nextProc->emit('processor.connect.done',[$nextProc,$this]);
            
        }catch(Exception $ie) {
            $e = new \Exception('Error connecting', 800041, $ie);
            $this->emit('processor.connect.error',[$e]);
        }
        
        return $nextProc;
    }

    private function _dispatchRequest(&$request,&$response,&$nextProc) {
        var_dump('Request'.$request->getPath());
        $msg=new \Greicodex\ServiceBuz\BaseMessage();
        $msg->setHeaders($request->getHeaders());
        $bodyBuffer='';
        $request->on('data',function($data) use(&$bodyBuffer){
            $bodyBuffer.=$data;
        });
        $request->on('end',function() use(&$msg,&$nextProc,&$bodyBuffer){
            $msg->setBody($bodyBuffer);
            var_dump($msg);
            try {
                var_dump('MessageDispatch' . get_class($this) .'->'.  get_class($nextProc));
                $nextProc->process($msg);
            }catch(\Exception $e) {
               $nextProc->emit('error',[$e,$msg]);
            }
        });
        $response->writeHead(202, array('Content-Type' => 'text/plain'));
        $response->write('Accepted');
        $response->end();
    }
    
    public function process(MessageInterface &$msg) {
        throw new \UnexpectedValueException();
    }
}
