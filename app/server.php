<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

$loader=require 'vendor/autoload.php';
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
$loader->register();
define('WEBDIR',  realpath(__DIR__.'/../web/')).'/';
use Greicodex\ServiceBuz\App;
$options='d'; //daemonize

$longoptions=[];
echo "Starting...\n";
$cmdoptions=getopt($options,$longoptions);
if(isset($cmdoptions['d'])) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('could not fork');
    }

    if($pid) {
        // parent
        echo "Damenoizing\n";
        exit(0);
    } else {
        echo "ServiceBuz started with PID: ".getmypid()."\n";
        fclose(STDERR);
        fclose(STDIN);
        fclose(STDOUT);
    }
}else{
    echo "ServiceBuz started with PID: ".getmypid()."\n";
}
$server = new App();
$server->init();
echo "Stopped...\n";