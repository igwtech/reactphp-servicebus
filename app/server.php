<?php
$loader=require 'vendor/autoload.php';
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
use Greicodex\ServiceBuz\Routers\BaseRouter;
$loader->register();

$loop = new React\EventLoop\StreamSelectLoop();

BaseRouter::registerSchema('timer','\Greicodex\ServiceBuz\Processors\Producers\TimerProducer');
BaseRouter::registerSchema('http','\Greicodex\ServiceBuz\Processors\HttpProcessor');
BaseRouter::registerSchema('file','\Greicodex\ServiceBuz\Processors\Consumers\FileConsumer');
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
    $routes['Hi'] = new BaseRouter($loop);
    $routes['Hi']->from('timer://dummy/?type=periodic&delay=0.1&data=Hello World')
            ->to('http://echo.opera.com')
            ->to('file:///tmp/?filename=javier&append=true')
            ->to('http://127.0.0.1/test/poster.php?httpMethod=POST')
            ->end();
    $routes['Bye'] = new BaseRouter($loop);
    $routes['Bye']->from('timer://dummy/?type=periodic&delay=0.1&data=Goodbye World')
            ->to('http://echo.opera.com')
            ->to('file:///tmp/?filename=javier&append=true')
            ->to('http://127.0.0.1/test/poster.php?httpMethod=POST')
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
