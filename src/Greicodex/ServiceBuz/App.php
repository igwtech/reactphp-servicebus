<?php
namespace Greicodex\ServiceBuz;
use Greicodex\ServiceBuz\Routers\BaseRouter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
/*
 * Idea: when creating a route use the processors to create a chain.
 * Each chain method our routeTo links to a routeFrom method that registers listeners
 * on the previous element of the chain
 * each element of the chain can generate events from its streams (http,ftp,file,glob) or 
 * events we new streams are created or old are removed.
 *  Questions: How to we transform streams into messages?
 *              How we handle batches?
 *              How we handle logging?
 * 
 * Routes should be able to activate/deactivate and could be Manually executed which
 * in term will send to all its chain elements an activation event.
 *
 * @author javier
 */
class App {
    private $loop;
    private $logger;
    private $routes;
    private $monitor;
    /**
     * 
     */
    public function __construct() {
        $this->logger = new Logger('main');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        //$this->logger->pushHandler(new StreamHandler('/tmp/server.log', Logger::INFO));
        $this->logger->pushHandler(new SyslogHandler('ServiceBuz', LOG_DAEMON, Logger::ERROR));
        \Monolog\Registry::addLogger($this->logger);
        $this->loop = new \React\EventLoop\StreamSelectLoop();
        //$this->loop =  React\EventLoop\Factory::create();
        $this->routes=[];
        
    }
    
    public function handle_signal($signo) {
        switch ($signo) {
            case SIGTERM:
            case SIGABRT:
                $this->shutdown();
                break;
        }
    }
    
    private function setUpSignals() {
        $this->logger->notice("Setting up signals");
        pcntl_signal(SIGTERM, [$this,'handle_signal']);
        pcntl_signal(SIGHUP,  [$this,'handle_signal']);
        pcntl_signal(SIGUSR1, [$this,'handle_signal']);
    }


    public function init(\SimpleXMLElement $config) {
        $this->logger->notice("Initializing");
        try {
            $this->setUpSignals();
            $this->logger->notice("Registering Adapters");
            foreach($config->registry->adapter as $adapter) {
                $this->logger->notice("Registering {$adapter['scheme']} -> {$adapter['classname']} ");
                BaseRouter::registerSchema((string)$adapter['scheme'],(string)$adapter['classname']);
            }
        
            $this->logger->notice("Creating Routes");
            foreach($config->routes->route as $route) {
                $obj =new BaseRouter($this->loop);
                $this->logger->notice("\tCreating Route: {$route['id']}");
                foreach($route as $k=>$v) {
                    $this->logger->notice("\t\tConnecting {$k} {$v['uri']} ");
                    $obj->{(string)$k}((string) $v['uri']);
                }
                $obj->end();
                $this->routes[(string)$route['id']] = $obj;
            }
            
            $this->monitor = new \Greicodex\ServiceBuz\Monitor($this->routes,$this->loop);
            $this->logger->notice("Entering main loop");
            $this->loop->run();

        }  catch (Exception $e) {
             \Monolog\Registry::getInstance('main')->addError($e->getMessage());
        }
    }
    
    
    
    public function shutdown() {
        $this->logger->notice("Stopping");
        $this->loop->stop();
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getRoutes() {
        return $this->routes;
    }

    public function getMonitor() {
        return $this->monitor;
    }

    public function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }

    public function setRoutes($routes) {
        $this->routes = $routes;
        return $this;
    }

    public function setMonitor($monitor) {
        $this->monitor = $monitor;
        return $this;
    }


    
}
