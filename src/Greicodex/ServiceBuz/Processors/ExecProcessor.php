<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors;

/**
 * Description of ExecProcessor
 *
 * @author javier
 */
class ExecProcessor extends BaseProcessor {
    public $commandLine;
    
    public function process(\Greicodex\ServiceBuz\MessageInterface &$msg) {
        \Monolog\Registry::getInstance('main')->addInfo("Executing  CMD:[{$this->commandLine}] CWD:[{$this->params['path']}]");
        $process = new \React\ChildProcess\Process($this->commandLine,$this->params['path'],$msg->getHeaders());
        \Monolog\Registry::getInstance('main')->addInfo("Process Started PID:[{$process->getPid()}]");
        $buffer ='';
        $errBuffer='';
        $process->on('exit', function($exitCode, $termSignal) use (&$msg,&$buffer,&$errBuffer,$process) {
            \Monolog\Registry::getInstance('main')->addInfo("Process Exited PID:[{$process->getPid()}] code: $exitCode");
            if($exitCode === 0) {
                $respMsg = new \Greicodex\ServiceBuz\BaseMessage();
                $respMsg->setHeaders($msg->getHeaders());
                $respMsg->addHeader('commandLine', $this->commandLine);
                $respMsg->addHeader('exitCode', $exitCode);
                $respMsg->addHeader('termSignal', $termSignal);
                $respMsg->setBody($buffer);
                $this->emit('message',[$respMsg]);
            }else{
                $this->emit('error',[new \Exception($errBuffer)]);
            }
        });
        
        $process->start($this->loop);
        \Monolog\Registry::getInstance('main')->addNotice('ChildProcess started');
        $process->stdout->on('data', function($output) use(&$buffer) {
            $buffer .= $output;
        });
        $process->stderr->on('data', function($output) use(&$errBuffer) {
            $errBuffer .= $output;
        });
        \Monolog\Registry::getInstance('main')->addDebug("Stdin:  [{$msg->getBody()}]");
        $process->stdin->end($msg->getBody());

    }

}
