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
                $this->loop->stop();
                break;
        }
    }
    
    private function setUpSignals() {
        $this->logger->notice("Setting up signals");
        pcntl_signal(SIGTERM, [$this,'handle_signal']);
        pcntl_signal(SIGHUP,  [$this,'handle_signal']);
        pcntl_signal(SIGUSR1, [$this,'handle_signal']);
    }


    public function init() {
        $this->logger->notice("Initializing");
        BaseRouter::registerSchema('timer','\Greicodex\ServiceBuz\Processors\Producers\TimerProducer');
        BaseRouter::registerSchema('http-client','\Greicodex\ServiceBuz\Processors\HttpClientProcessor');
        BaseRouter::registerSchema('http','\Greicodex\ServiceBuz\Processors\Producers\HttpServerProducer');
        BaseRouter::registerSchema('file','\Greicodex\ServiceBuz\Processors\Consumers\FileConsumer');
        BaseRouter::registerSchema('dir','\Greicodex\ServiceBuz\Processors\Producers\FileProducer');
        BaseRouter::registerSchema('queue-consumer','\Greicodex\ServiceBuz\Processors\Producers\AMQPProducer');
        BaseRouter::registerSchema('queue-producer','\Greicodex\ServiceBuz\Processors\Consumers\AMQPConsumer');
        try {
            $this->setUpSignals();
            $this->logger->notice("Creating Routes");
//            $this->routes['route-file'] = new BaseRouter($this->loop);
//            $this->routes['route-file']->from('dir://monitor/tmp/input?delay=0.01')
//                    ->to('http-client://127.0.0.1/test/echo.php?httpMethod=POST') 
//                    ->log('got it')
//                    ->end();
//            $this->routes['route-http'] = new BaseRouter($this->loop);
//            $this->routes['route-http']->from('http://localhost:12345/as3')
//                    //->to('http-client://echo.opera.com?httpMethod=POST')
//                    ->log('got it')
//                    ->to('http-client://127.0.0.1/test/poster.php?httpMethod=POST')
//                    ->to('file:///tmp/input/?filename=javier')
//                    ->end();
            $this->routes['route-mq'] = new BaseRouter($this->loop);
            $this->routes['route-mq']->from('queue-consumer://dtsmq:dts#2015@localhost/inputQueue')
                    ->to('queue-producer://dtsmq:dts#2015@localhost/outBox?routingKey=outputQueue')
                    ->end();
//            $this->routes['route-mqfile'] = new BaseRouter($this->loop);
//            $this->routes['route-mqfile']->from('dir://localhost/tmp/?delay=0.01')
//                    ->to('queue-producer://localhost/outBox?routingKey=outputQueue')
//                    ->end();
            $this->routes['route-mqtimer'] = new BaseRouter($this->loop);
            $this->routes['route-mqtimer']->from('timer://localhost/?delay=0.1')
                    ->to('queue-producer://dtsmq:dts#2015@localhost/outBox?routingKey=outputQueue')
                    ->end();
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
