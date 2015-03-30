<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
/**
 * Description of HttpProducer
 *
 * @author javiermunoz
 */
class HttpClientProcessor extends BaseProcessor {
    /**
     * 
     * @var React\Dns\Resolver\Resolver 
     */
    protected static $dnsResolver;
    protected static $factory;
    
    
    public $httpMethod;
    public $contentType;
    public $userAgent;

    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        if(null === self::$dnsResolver){
            $dnsResolverFactory = new \React\Dns\Resolver\Factory();
            self::$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        }
        if(null === self::$factory) {
            self::$factory = new \React\HttpClient\Factory();
        }
        $this->httpMethod='GET';
        $this->contentType='application/x-www-form-urlencoded';
        $this->userAgent='Greicodex/ServiceBus 1.0';
    }

    public function configure() {
        $this->parseParams();
    }
    
    
    protected function buildUrl($parts) {
        $url='';
        if(function_exists('http_build_url')) {
            $url= http_build_url($parts);
        }else{
            $url .=(($parts['scheme'] =='https-client')?'https://':'http://');
            $url .=(!empty($parts['host']))?$parts['host']:'';
            $url .=(!empty($parts['port']))?':'.$parts['port']:'';
            $url .=(!empty($parts['path']))?$parts['path']:'';
            $url .=(!empty($parts['query']))?'?'.$parts['query']:'';
            $url .=(!empty($parts['fragment']))?'#'.$parts['fragment']:'';
        }
        return $url;
    }

    private function fromMessageToHttpRequest(MessageInterface &$msg) {
        
        $headers= array();
        foreach($msg->getHeaders() as $k=>$v) {
            if(is_scalar($v)) {
                $headers[$k]=$v;
            }elseif($v instanceof \DateTime){
                $headers[$k]=$v->getTimestamp();
            }else{
                try {
                    $headers[$k]= "$v";
                } catch (Exception $ex) {
                    // SKip
                }
            }
        }
        $headers= array_merge(array(
                    'Host'=>$this->params['host'],
                    'Content-Type'=>$this->contentType,
                    'User-Agent'=>$this->userAgent,
                    'Connection'=>'close'
                
                ),$headers);
        $headers['Connection']='Close';
        unset($headers['Keep-Alive']); // We don't support keep alives. Remove them to avoid lingering connections
        $data = $msg->getBody();
        $uriParams = $this->params;
        
        $uriQuery=(isset($uriParams['query']))?$uriParams['query']:'';
        \Monolog\Registry::getInstance('main')->addDebug($uriParams);
        if(null != $data && $this->httpMethod == 'GET') {
            if(is_array($data) ) {
                $uriQuery = http_build_query($data);
            }else{
                $uriQuery = "&".$data;
            }
            $data='';
        }
        $uriParams['query'] = $uriQuery;
        $url = $this->buildUrl($uriParams);
        
        $headers['Content-Length']=strlen((string)$data);
        return array($url,$headers,$data);
    }
    
    public function process(MessageInterface &$msg) {
        \Monolog\Registry::getInstance('main')->addNotice('Process HTTP');
        //Extract in Marshalling message method
        
        list($url,$headers,$data)= $this->fromMessageToHttpRequest($msg);
        
        $client = self::$factory->create($this->loop, self::$dnsResolver);

        \Monolog\Registry::getInstance('main')->addInfo($this->httpMethod.' '.$url);
        \Monolog\Registry::getInstance('main')->addDebug($headers);
        \Monolog\Registry::getInstance('main')->addDebug($data);
        $request = $client->request($this->httpMethod, $url,$headers);
        $request->on('headers-written',function($request) use($data){
            \Monolog\Registry::getInstance('main')->addNotice('Connect!');
            if($this->httpMethod == 'POST') {
                $request->write($data);
            }
            $request->end();
            
        });
        $request->on('error',function($e) {
            $this->emit('error',[$e]);
        });
        $request->on('response', function (\React\HttpClient\Response $response) {
            
            //Separate on Method
            $msg = new \Greicodex\ServiceBuz\BaseMessage();
            $msg->setHeaders($response->getHeaders());
            $buffer='';
            $response->on('data', function ($data) use (&$msg,&$buffer) {
                \Monolog\Registry::getInstance('main')->addNotice('Data!');
               $buffer.=$data;
            });
            $response->on('end',function() use(&$msg,&$buffer,&$response)  {
               \Monolog\Registry::getInstance('main')->addNotice('End!'.$response->getCode());
               $msg->setBody($buffer);
               
               if($response->getCode() < 300) {
                   \Monolog\Registry::getInstance('main')->addDebug((string)$buffer);
                   $this->emit('message',[$msg]);
               }else{
                   $this->emit('error',[new \ErrorException('Http Error'),$msg]);
                   \Monolog\Registry::getInstance('main')->addError($msg->getBody());
               }
               
            });
        });
        $request->writeHead();
    }

}
