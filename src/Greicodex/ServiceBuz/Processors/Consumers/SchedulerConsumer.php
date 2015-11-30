<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Greicodex\ServiceBuz\Processors\Consumers;
use React\EventLoop\LoopInterface;
use Greicodex\ServiceBuz\Processors\BaseProcessor;
use React\EventLoop\Timer\TimerInterface;
use Greicodex\ServiceBuz\MessageInterface;
/**
 * Description of SchedulerConsumer
 *
 * @author javier
 */
class SchedulerConsumer extends BaseProcessor {
    const TYPE_PERIODIC='periodic';
    
    /**
     * The Scheduler cron expressions for representing recurring schedules. 
     * Cron expressions are made up of several fields, and each field represents a measurement of time. 
     * The fields in a cron expression are as follows: minute, hour, day of month, month, day of week, 
     * and an optional year. 
     * Positional field list for Cron Expressions 
     *   *    *    *    *    *    *
     *   -    -    -    -    -    -
     *   |    |    |    |    |    |
     *   |    |    |    |    |    + year [optional]
     *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
     *   |    |    |    +---------- month (1 - 12)
     *   |    |    +--------------- day of month (1 - 31)
     *   |    +-------------------- hour (0 - 23)
     *   +------------------------- min (0 - 59)
     * @var string 
     */
    public $crontab;
    public $data;
    public $year;
    public $dayofweek;
    public $month;
    public $dayofmonth;
    public $hour;
    public $min;
    
    private $resolution;
    
    private $cron;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        parent::__construct($loop,$canceller);
        $this->type=TimerConsumer::TYPE_PERIODIC;
        $this->resolution=30.0; // 30sec resolution
        $this->data='';
        $this->year='*';
        $this->dayofweek='*';
        $this->month='*';
        $this->dayofmonth='*';
        $this->hour='*';
        $this->min='*';
        $this->crontab=false;
    }

    
    public function configure() {
        parent::configure();
        
        if($this->cron === null ) {
            if($this->crontab === false) {
                $this->crontab =sprintf('%s %s %s %s %s %s',$this->min,$this->hour,$this->dayofmonth,$this->month,$this->dayofweek,$this->year);
            }
            $this->cron = \Cron\CronExpression::factory($this->crontab);
        }
        \Monolog\Registry::getInstance('main')->addDebug("Schedule:".$this->crontab);
        \Monolog\Registry::getInstance('main')->addDebug(print_r($this->params,true));
        $this->loop->addPeriodicTimer($this->resolution, function(TimerInterface $t) {
            $this->onTimer();
        });
        \Monolog\Registry::getInstance('main')->addNotice('First run:'. $this->cron->getNextRunDate()->format('c'));
    }

    protected function onTimer() {
        if(!$this->cron->isDue()) {
            return;
        }
        \Monolog\Registry::getInstance('main')->addNotice('Running Scheduled Task');
        $msg=new \Greicodex\ServiceBuz\BaseMessage();
        $msg->setHeader('Timestamp', (new \DateTime())->format('c'));
        $msg->setBody($this->data);
        $this->process($msg); 
        if($msg !== null) {
            $this->emit('message',[$msg]);
        }
        \Monolog\Registry::getInstance('main')->addNotice('Next run:'. $this->cron->getNextRunDate()->format('c'));
    }

    public function process(MessageInterface &$msg) {
        // DO nothing
    }

}
