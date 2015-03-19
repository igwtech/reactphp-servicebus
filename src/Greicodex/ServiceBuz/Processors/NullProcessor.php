<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use Greicodex\ServiceBuz\MessageInterface;
/**
 * Description of NullProcessor
 *
 * @author javier
 */
class NullProcessor extends BaseProcessor {
    public function process(MessageInterface &$msg) {
        $history=@$msg->getHeader('trace') or [];
        $history[]=  get_class($this);
        $msg->setHeader('trace',$history );
        return $msg;
    }
}
