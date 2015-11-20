<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

$loader=require 'vendor/autoload.php';
define('WEBDIR',  realpath(__DIR__.'/../web/'));
define('WORKDIR',  realpath(__DIR__.'/../'));
define('CONFIG',realpath(__DIR__.'/config.xml'));
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
$config = simplexml_load_file(CONFIG);
$server = new App();
$server->init($config);
echo "Stopped...\n";