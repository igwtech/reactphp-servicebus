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
 * Description of HttpProducer
 *
 * @author javiermunoz
 */
class HttpProducer extends BaseProcessor {
    /**
     * 
     * @var React\Dns\Resolver\Resolver 
     */
    protected static $dnsResolver;
    protected static $factory;
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        if(null === HttpProducer::$dnsResolver){
            $dnsResolverFactory = new \React\Dns\Resolver\Factory();
            HttpProducer::$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        }
        if(null === HttpProducer::$factory) {
            HttpProducer::$factory = new \React\HttpClient\Factory();
        }
    }

    public function configure() {
        $this->loop->addPeriodicTimer(0.1, function(TimerInterface $t) {
            $msg=new \Greicodex\ServiceBuz\BaseMessage();
            $msg->setHeaders(array(
            'Host'=>'echo.opera.com',
            'Content-Type'=>'text/plain',
            'User-Agent'=>'AsyncPHP'
            ));
            $msg->setBody("This is a sample, hello world");
           $this->process($msg); 
        });
    }

    public function process(MessageInterface &$msg) {
        $client = HttpProducer::$factory->create($this->loop, HttpProducer::$dnsResolver);
        $data=$msg->getBody();
        $msg->setHeader('Content-Length',strlen($data));
        $headers=$msg->getHeaders();
        $request = $client->request('POST', 'https://echo.opera.com/',$headers);
        $request->on('headers-written',function($request) use($data){
            var_dump('CONNECTED!');
            $request->write($data);
            $request->end();
        });
        $request->on('error',function($e) {
            $this->emit('error',[$e]);
        });
        $request->on('response', function (\React\HttpClient\Response $response) {
            $msg = new \Greicodex\ServiceBuz\BaseMessage();
            $msg->setHeaders($response->getHeaders());
            $buffer='';
            $response->on('data', function ($data) use (&$msg,&$buffer) {
                var_dump('Data');
               $buffer.=$data;
            });
            $response->on('end',function() use(&$msg,&$buffer) {
                var_dump('End');
               $msg->setBody($buffer);
               $this->emit('message',[$msg]);
            });
        });
        $request->writeHead();
    }

}
