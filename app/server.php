<?php
$loader=require 'vendor/autoload.php';
$loader->add('Greicodex\\ServiceBuz',__DIR__.'/../src');
$loader->register();
define('WEBDIR',  realpath(__DIR__.'/../web/')).'/';

use Greicodex\ServiceBuz\App;

$pid = pcntl_fork();
if ($pid == -1) {
    die('could not fork');
}

if($pid) {
    // parent
} else {
    echo "ServiceBuz started with PID: ".getmypid()."\n";
    fclose(STDERR);
    fclose(STDIN);
    fclose(STDOUT);
    $server = new App();
    $server->init();
}