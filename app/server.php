<?php
$loader=require 'vendor/autoload.php';
use React\EventLoop\Timer\TimerInterface;
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
$loader->register();
var_dump($loader->getPrefixes());
define('PROC_COUNT',10);

$loop = React\EventLoop\Factory::create();
 Greicodex\ServiceBuz\Processors\ProcessorFactory::init(__DIR__.'/config.xml');



function getRoute() {
    global $loop;
    $processors=array();
    for($i=0;$i<= PROC_COUNT;$i++) {
        $processors[]= Greicodex\ServiceBuz\Processors\ProcessorFactory::create("trace://dummy/?format=Instance %s[%d]",$loop);
    }
    $i=0;
    while($i<PROC_COUNT) {
        $processors[$i++]->on('processor.output',$processors[$i]->feed);
    }
        
    $processors[$i]->on('processor.output',function(\Greicodex\ServiceBuz\MessageInterface $msg) {
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


$loop->addPeriodicTimer(0.01, function(TimerInterface $t)  {
    echo "Feeding new Message\n";
    $msg = new Greicodex\ServiceBuz\BaseMessage();
    $msg->setBody('Hello World');
    $entry = getRoute();
    $entry->feed($msg);
    echo memory_get_peak_usage()." bytes\n";
});
$loop->run();