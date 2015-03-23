<?php
$loader=require 'vendor/autoload.php';
use React\EventLoop\Timer\TimerInterface;
use React\Stream\Stream;
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
$loader->register();
var_dump($loader->getPrefixes());
define('PROC_COUNT',10);

$loop = React\EventLoop\Factory::create();

$fd=fopen('glob:///tmp/*.test','r');
$s= new Stream($fd,$loop);
$s->on('data',function($data,$stream) {
    var_dump($data);
});
$s->resume();
$loop->run();
die();
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
function getRoute() {
    global $loop;
    $processors=array();
    for($i=0;$i<= PROC_COUNT;$i++) {
        $processors[]= Greicodex\ServiceBuz\Processors\BaseProcessor::FactoryCreate($loop);
    }
    $i=0;
    while($i<PROC_COUNT) {
        $processors[$i++]->promise()->then($processors[$i]->feed);
    }
        
    $processors[$i]->promise()->then(function(\Greicodex\ServiceBuz\MessageInterface $msg) {
        echo "Fin de cadena!\n";
        var_dump($msg);
    });
    return $processors[0];
}
//$socket = new React\Socket\Server($loop);
//$http = new React\Http\Server($socket, $loop);
//$http->on('request', $app);
//echo "Server running at http://127.0.0.1:1337\n";
//$socket->listen(1337);


$loop->addPeriodicTimer(0.01, function(TimerInterface $t) {
    echo "Feeding new Message\n";
    $msg = new Greicodex\ServiceBuz\BaseMessage();
    $msg->setBody('Hello World');
    $entry = getRoute();
    $entry->feed($msg);
});
$loop->run();
