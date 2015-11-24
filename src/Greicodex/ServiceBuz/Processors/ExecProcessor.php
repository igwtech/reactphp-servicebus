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
        \Monolog\Registry::getInstance('main')->addInfo("Executing  [{$this->commandLine}]");
        $process = new \React\ChildProcess\Process($this->commandLine,$this->params['path'],$msg->getHeaders());
        
        $buffer ='';
        $process->on('exit', function($exitCode, $termSignal) use (&$msg,&$buffer) {
            \Monolog\Registry::getInstance('main')->addInfo("Process Exited  [{$this->commandLine}] code: $exitCode");
            $respMsg = new \Greicodex\ServiceBuz\BaseMessage();
            $respMsg->setHeaders($msg->getHeaders());
            $respMsg->addHeader('commandLine', $this->commandLine);
            $respMsg->addHeader('exitCode', $exitCode);
            $respMsg->addHeader('termSignal', $termSignal);
            $respMsg->setBody($buffer);
            $this->emit('message',[$respMsg]);
        });
        
        $process->start($this->loop);
        \Monolog\Registry::getInstance('main')->addNotice('ChildProcess started');
        $process->stdout->on('data', function($output) use(&$buffer) {
            $buffer .= $output;
        });
        \Monolog\Registry::getInstance('main')->addNotice("Stdin:  [{$msg->getBody()}]");
        $process->stdin->end($msg->getBody());

    }

}
