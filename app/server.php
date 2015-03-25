<?php
$loader=require 'vendor/autoload.php';
use React\EventLoop\Timer\TimerInterface;
use React\Stream\Stream;
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
$loader->register();
//var_dump($loader->getPrefixes());
define('PROC_COUNT',10);
$loop = React\EventLoop\Factory::create();

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
    global $loop;
    $processors=array();
    
        $processors[0]= Greicodex\ServiceBuz\Processors\Producers\HttpProducer::FactoryCreate('http://echo.opera.com',$loop);
        $processors[1]= Greicodex\ServiceBuz\Processors\Consumers\FileConsumer::FactoryCreate('file:///tmp/', $loop);
        $processors[0]->forwardTo($processors[1]);
    
    //return $processors[0];
}  catch (Exception $e) {
    var_dump($e);
}
//$socket = new React\Socket\Server($loop);
//$http = new React\Http\Server($socket, $loop);
//$http->on('request', $app);
//echo "Server running at http://127.0.0.1:1337\n";
//$socket->listen(1337);

$loop->run();
