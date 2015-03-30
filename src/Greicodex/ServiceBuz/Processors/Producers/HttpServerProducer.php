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
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
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
    
    private function setupListener($port=80) {
        if(!isset(self::$httpListeners[$port])) {
            $http = self::$httpListeners[$port]=new HttpServer\HttpServerListener($this->loop);
            //Configure handlers
            $http->on('request', function (\React\Http\Request $request,  \React\Http\Response $response) {
                \Monolog\Registry::getInstance('main')->addNotice('HTTP Request');
                $bodyBuffer='';
                $msg=new \Greicodex\ServiceBuz\BaseMessage();
                $headers=$request->getHeaders();
                ($headers);
                $msg->setHeaders($headers);                
                
                if(!$msg->getHeader('Content-Length')) {
                    $this->dispatchRequest($request, $response, $msg);
                    return;
                }
                $request->on('data',function($data) use(&$request,&$response,&$msg,&$bodyBuffer){
                    \Monolog\Registry::getInstance('main')->addDebug('HTTP Data "'.$data.'"');
                    $bodyBuffer.=$data;
                    $msg->setBody($bodyBuffer);
                    if(intval($msg->getHeader('Content-Length')) === strlen($bodyBuffer)) {
                        \Monolog\Registry::getInstance('main')->addNotice('dispatching');
                        $this->dispatchRequest($request, $response, $msg);
                    }
                    \Monolog\Registry::getInstance('main')->addNotice('Received - '.strlen($bodyBuffer).' bytes');
                });
                $request->on('end',function() use(&$request,&$response,&$msg,&$bodyBuffer){
                    \Monolog\Registry::getInstance('main')->addNotice('HTTP End');
                    //$msg->setBody($bodyBuffer);
                    
                    //$this->dispatchRequest($request, $response, $msg);
                });
               
            });
            
            $http->listen($port,'0.0.0.0');
            \Monolog\Registry::getInstance('main')->addInfo("Listening on port $port");
        }
    }
    
    public function configure() {
        parent::parseParams();
        $this->setupListener($this->params['port']);
    }
    
    public function forwardTo(ProcessorInterface &$nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {

            if(!isset(self::$processorMap[$this->params['path']])){
                self::$processorMap[$this->params['path']]=array();
            }
            self::$processorMap[$this->params['path']][]=$nextProc;
            $nextProc->emit('processor.connect.done',[$nextProc,$this]);
            
        }catch(Exception $ie) {
            $e = new \Exception('Error connecting', 800041, $ie);
            $this->emit('processor.connect.error',[$e]);
        }
        
        return $nextProc;
    }

    private function dispatchRequest(&$request,&$response,&$msg) {
        \Monolog\Registry::getInstance('main')->addNotice('Request '.$request->getPath());
        if(!in_array($request->getPath(),  array_keys(self::$processorMap) )) {
            $response->writeHead(404, array('Content-Type' => 'text/plain'));
            $response->write('Not Found');
            $response->end();
            return;
        }

        $listeners =self::$processorMap[$request->getPath()];
        foreach($listeners as $k=>$nextProc) {
            try {
                \Monolog\Registry::getInstance('main')->addDebug($nextProc->getParams());
                \Monolog\Registry::getInstance('main')->addDebug('MessageDispatch ' . get_class($this) .'->'.  get_class($nextProc));
                $nextProc->process($msg);
            }catch(\Exception $e) {
                $nextProc->emit('error',[$e,$msg]);
                
                $response->writeHead(500, array('Content-Type' => 'text/plain'));
                $response->write('Internal server error');
                $response->end();
                return;
            }
        }
        $response->writeHead(202, array('Content-Type' => 'text/plain'));
        $response->write('Accepted');
        $response->end();
    }
    
    public function process(MessageInterface &$msg) {
        throw new \UnexpectedValueException();
    }
}
