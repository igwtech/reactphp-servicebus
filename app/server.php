<?php
$loader=require 'vendor/autoload.php';
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
use Greicodex\ServiceBuz\Routers\BaseRouter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$loader->register();
define('WEBDIR',  realpath(__DIR__.'/../web/')).'/';


$log = new Logger('main');
$log->pushHandler(new StreamHandler(__DIR__.'/debug.log', Logger::DEBUG));
\Monolog\Registry::addLogger($logger);
$loop = new React\EventLoop\StreamSelectLoop();
//$loop =  React\EventLoop\Factory::create();

BaseRouter::registerSchema('timer','\Greicodex\ServiceBuz\Processors\Producers\TimerProducer');
BaseRouter::registerSchema('http-client','\Greicodex\ServiceBuz\Processors\HttpClientProcessor');
BaseRouter::registerSchema('http','\Greicodex\ServiceBuz\Processors\Producers\HttpServerProducer');
BaseRouter::registerSchema('file','\Greicodex\ServiceBuz\Processors\Consumers\FileConsumer');
BaseRouter::registerSchema('dir','\Greicodex\ServiceBuz\Processors\Producers\FileProducer');
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
 */


try {
    /*
    $routes['Hi'] = new BaseRouter($loop);
    $routes['Hi']->from('timer://dummy/?type=periodic&delay=10.1&data=Hello World')
            ->to('http-client://google.com')
            ->to('file:///tmp/?filename=javier&append=true')
            ->to('http-client://127.0.0.1/test/poster.php?httpMethod=POST')
            ->end();
    
    $routes['Bye'] = new BaseRouter($loop);
    $routes['Bye']->from('timer://dummy/?type=periodic&delay=0.1&data=Goodbye World')
            ->to('http-client://echo.opera.com')
            ->to('file:///tmp/?filename=javier&append=true')
            ->to('http-client://127.0.0.1/test/poster.php?httpMethod=POST')
            ->end();
    */
    $routes['route-file'] = new BaseRouter($loop);
    $routes['route-file']->from('dir://monitor/tmp/input')
            ->to('http-client://echo.opera.com?httpMethod=POST')
            ->log('got it')
            ->end();
    $routes['route-http'] = new BaseRouter($loop);
    $routes['route-http']->from('http://localhost:12345/as3')
            //->to('http-client://echo.opera.com?httpMethod=POST')
            ->log('got it')
            ->to('http-client://127.0.0.1/test/poster.php?httpMethod=POST')
            ->to('file:///tmp/input/?filename=javier')
            ->end();
    $monitor = new Greicodex\ServiceBuz\Monitor($routes,$loop);
}  catch (Exception $e) {
    var_dump($e);
}

    
//$socket = new React\Socket\Server($loop);
//$http = new React\Http\Server($socket, $loop);
//$http->on('request', $app);
//echo "Server running at http://127.0.0.1:1337\n";
//$socket->listen(1337);

$loop->run();
