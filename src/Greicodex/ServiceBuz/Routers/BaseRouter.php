<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Routers;
use React\EventLoop\LoopInterface;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
/**
 * Description of BaseRouter
 *
 * @author javier
 */
class BaseRouter {
    protected $msgCount;
    protected $errCount;
    protected $deliverCount;
    protected $processors;
    protected static $registeredSchemas;
    public function __construct(LoopInterface $loop) {
        $this->loop = $loop;
        $this->processors=array();
        $this->msgCount=0;
        $this->errCount=0;
        $this->deliverCount=0;
    }
    
    public static function registerSchema($schema,$classname) {
        if(BaseRouter::$registeredSchemas==null) {
            BaseRouter::$registeredSchemas=array();
        }
        if(!class_exists($classname)) {
            throw new \ErrorException("Classname $classname doesn't exists");
        }
        
        BaseRouter::$registeredSchemas[$schema]=$classname;
    }
    public function __get($name) {
        if(\in_array($name,  \get_class_methods($this))) {
            return array($this,$name);
        }
    }
    public function getFactory($uri) {
        $uriParams=parse_url($uri);
        $scheme = $uriParams['scheme'];
        if(false === $scheme) {
            throw new \ErrorException("Invalid Url $uri");
        }
        if(!isset(BaseRouter::$registeredSchemas[$scheme])) {
            throw new \ErrorException("Invalid Scheme $scheme");
        }
        $factory= BaseRouter::$registeredSchemas[$scheme];
        return call_user_func(array($factory,'FactoryCreate'), $uri,$this->loop);
    }
    
    public function to($uri) {
        $processor = $this->getFactory($uri);
        $processor->on('processor.connect.done',function() use($uri) {
            \Monolog\Registry::getInstance('main')->addInfo($uri . ' connected');
        });
        $processor->on('error',function ($e,$msg=null) {
            \Monolog\Registry::getInstance('main')->addError($e->getMessage());
            $this->errCount++;
            \Monolog\Registry::getInstance('main')->addAlert('ERROR: '.$this->errCount);
        });
        $this->processors[count($this->processors) -1 ]->forwardTo($processor);
        $this->processors[] = &$processor;
        return $this;
    }
    public function from($uri) {
        $processor = $this->getFactory($uri);
        $processor->on('processor.connect.done',function() use($uri) {
            \Monolog\Registry::getInstance('main')->addInfo($uri . ' connected');
        });
        $processor->on('error',function($e,$msg) {
            $this->errCount++;
            \Monolog\Registry::getInstance('main')->addAlert('ERROR: '.$this->errCount);
        });
        $processor->on('message',function ($msg) {
            $this->msgCount++;
            \Monolog\Registry::getInstance('main')->addInfo('MSG: '.$this->msgCount);
        });
        $this->processors[] = &$processor;
        return $this;
    }

    public function end() {
        $this->processors[count($this->processors) -1 ]->on('message',function ($msg) {
            
            $this->deliverCount++;
            \Monolog\Registry::getInstance('main')->addInfo('DELIVERIES: '.$this->deliverCount);
        });
    }
    
    public function getStatus() {
        return array('msg'=>$this->msgCount,'err'=>$this->errCount,'sent'=>  $this->deliverCount);
    }
    public function log($format) {
        $this->processors[count($this->processors) -1 ]->on('message',function ($msg) use ($format) {
            
            \Monolog\Registry::getInstance('main')->addInfo('LOG:'.$format);
        });
        return $this;
    }
}
