<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors;
use React\EventLoop\LoopInterface;
/**
 * Description of ProcessorFactory
 *
 * @author javier
 */
class ProcessorFactory {
    private static $config=false;


    public static function init($configFilename) {
        ProcessorFactory::$config = simplexml_load_file($configFilename);
        if(ProcessorFactory::$config === false) {
            throw new \ErrorException("Failed to load configuration $configFilename");
        }
        
    }
    
    public static function create($uri,LoopInterface  $loop) {
        if(ProcessorFactory::$config === false) {
            throw new \ErrorException("Failed to load configuration");
        }
        $uriparams=parse_url($uri);
        foreach(ProcessorFactory::$config->adapter as $adapter) {

            if((string)$adapter['scheme'] === $uriparams['scheme'] ) {
                $classname = (string)$adapter['classname'];
                $instance= new $classname($loop);
                parse_str($uriparams['query'], $uriparams['query']);
                $instance->configure($uriparams);

                return $instance;
                break;
            }
        }
        throw new \ErrorException("Unable to find adapter for scheme ".$uriparams['scheme'] ."://");
       
    }
    
}
