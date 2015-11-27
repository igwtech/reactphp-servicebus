<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
date_default_timezone_set('UTC');
include_once __DIR__.'/../src/bootstrap.php';
use Greicodex\ServiceBuz\App;
use Greicodex\OptionParser;



//MAIN
function main() {  
    //Enable Garbage Collection
    gc_enable();
    $parser = new OptionParser();
    $appName=(isset($argv[0]))?$argv[0]:APP_NAME;
    $parser->addHead("Usage: {$appName} [ options ]\n");
    try {
        $start = microtime(true);
        $parser->addRule('c|config:', 'Use the <file> as configuration (default: config.xml)');
        $parser->addRule('d|daemon', 'Fork daemon process and return to console');
        
        $parser->parse();
        if($parser->getOption('d|daemon')) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('could not fork');
            }

            if($pid) {
                // parent
                echo "Daemonizing\n";
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
        
        // Check Architecture
        if (PHP_INT_MAX < 9223372036854775807) {
            ///throw new \ErrorException("Please use 64bit architecture (recommended)");
            Logger::warn("Please use 64bit architecture (recommended)");
            print "Please use 64bit architecture (recommended)\n";
        }
        
	$lock_path="/var/run/$appName.pid";
        //First, check to see if the script is already running
        /**
        * Basically, it reads the file, and then tries to posix_kill the pid contained inside. 
        * Note, that when you use a signal of 0 to posix_kill, it merely tells you if the call will succeed (meaning that the process is running).
        **/
        $data = @file_get_contents($lock_path);
        if ($data && posix_kill($data, 0)) {
                print "Unable To Attain Lock, Another Process Is Still Running\n";
                throw new RuntimeException(
                        'Unable To Attain Lock, Another Process Is Still Running'
                );
        }
        @\file_put_contents($lock_path, posix_getpid());        
        
        if(($configFilename=$parser->getOption('c|config')) === null ){
            $configFilename= "config.xml";
        }
        $config = simplexml_load_file($configFilename);
        $server = new App();
        $server->init($config);
        echo "Stopped...\n";
        return 0;
    }  catch (\Exception $e) {
        echo $e->getMessage();
        return -1;
    }
}


echo "Starting...\n";
exit(main());


