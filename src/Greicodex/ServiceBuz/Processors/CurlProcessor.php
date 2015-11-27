<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use \KHR\React\Curl\Curl;
use \KHR\React\Curl\Exception;

/**
 * Description of CurlProcessor
 *
 * @author javier
 */
class CurlProcessor extends BaseProcessor {
    public $maxRequests;
    public $sleepTime;
    /**
     * Request method POST,GET,OPTIONS,etc
     * @var string
     */
    public $httpMethod;
    /**
     * Mime content type
     * @var string
     */
    public $contentType;
    /**
     * Http user agent
     * @var string
     */
    public $userAgent;
    protected static $curl=null;
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        if(CurlProcessor::$curl === null) {
             CurlProcessor::$curl= new Curl($loop);
        }
        $this->httpMethod='GET';
        $this->contentType='application/x-www-form-urlencoded';
        $this->userAgent='Greicodex/ServiceBus 1.0';
        $this->maxRequests=3;
        $this->sleepTime=1.0;
    }
    
    /**
     * Configuration, parses the URL params and extract internal variables
     */
    public function configure() {
        $this->parseParams();
        // Config
        CurlProcessor::$curl->client->setMaxRequest($this->maxRequests);
        CurlProcessor::$curl->client->setSleep(6, 1.0, false); // 6 request in 1 second
        //$this->curl->client->setCurlOption([CURLOPT_AUTOREFERER => true, CURLOPT_COOKIE => 'fruit=apple; colour=red']); // default options
        CurlProcessor::$curl->client->enableHeaders();
    }
    
    /**
     * Builds the request URL. In case of a GET request includes the data in the Query string
     * Basically does a reverse of http_query_parse
     * @param array $parts
     * @return string valid Url
     */
    protected function buildUrl($parts) {
        $url='';
        if(function_exists('http_build_url')) {
            $url= http_build_url($parts);
        }else{
            $url .=$parts['scheme'].'://';
            $url .=(!empty($parts['host']))?$parts['host']:'';
            $url .=(!empty($parts['port']))?':'.$parts['port']:'';
            $url .=(!empty($parts['path']))?$parts['path']:'';
            $url .=(!empty($parts['query']))?'?'.$parts['query']:'';
            $url .=(!empty($parts['fragment']))?'#'.$parts['fragment']:'';
        }
        return $url;
    }
        /**
     * Converts a Message into an HTTP outbound request (client)
     * @param MessageInterface $msg
     * @return array HttpRequest (url,headers,data)
     */
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
        \Monolog\Registry::getInstance('main')->addDebug(print_r($uriParams,true));
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
    
    /**
     * Consumes a Message to into an HTTP Request, generates another message as Event
     * In case of using this processor as a Producer it must consume a timer Message
     * HttpClient Requests can trigger by themselves
     * @param MessageInterface $msg
     */
    public function process(MessageInterface &$msg) {
        \Monolog\Registry::getInstance('main')->addNotice('Process HTTP');
        //Extract in Marshalling message method
        
        list($url,$headers,$data)= $this->fromMessageToHttpRequest($msg);
        
        \Monolog\Registry::getInstance('main')->addInfo($this->httpMethod.' '.$url);
        \Monolog\Registry::getInstance('main')->addDebug(print_r($headers,true));
        \Monolog\Registry::getInstance('main')->addDebug($data);
        //$this->curl->add($headers);
        $cb_err=function(\Exception $e){  $this->emit('error',[$e]); };
        
        $cb_ok=function (\MCurl\Result $result) {
            
            //Separate on Method
            $msg = new \Greicodex\ServiceBuz\BaseMessage();
            $msg->setHeaders($result->getHeaders());
            \Monolog\Registry::getInstance('main')->addNotice('End!'.$result->getHttpCode());

            $msg->setBody($result->getBody());
               
            if($result->hasError()) {
                $this->emit('error',[new \ErrorException('Http Error'),$msg]);
                \Monolog\Registry::getInstance('main')->addError($msg->getBody());
            }else{
                \Monolog\Registry::getInstance('main')->addDebug((string)$msg->getBody());
                $this->emit('message',[$msg]);
            }
               
        };
        if($this->httpMethod === CurlProcessor::METHOD_GET) {
            CurlProcessor::$curl->get($url,[])->then($cb_ok,$cb_err);
        }elseif ($this->httpMethod === CurlProcessor::METHOD_POST) {
            CurlProcessor::$curl->post($url,[$msg->getBody()],[])->then($cb_ok,$cb_err);
        }elseif ($this->httpMethod === CurlProcessor::METHOD_PUT) {
            CurlProcessor::$curl->post($url,[$msg->getBody()],[])->then($cb_ok,$cb_err);
        }else{
            throw new \Exception("Invalid Method");
        }
    }
}
