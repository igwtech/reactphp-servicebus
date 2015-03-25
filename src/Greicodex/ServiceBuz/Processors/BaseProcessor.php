<?php

namespace Greicodex\ServiceBuz\Processors;
use Greicodex\ServiceBuz\Processors\ProcessorInterface;
use Greicodex\ServiceBuz\MessageInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
/**
 * Description of BaseProcessor
 *
 * @author javier
 */
abstract class BaseProcessor implements ProcessorInterface {
    use \Evenement\EventEmitterTrait;
    protected $loop;
    protected $deferred;
    protected $params;
    
    protected function __construct(LoopInterface $loop, callable $canceller = null) {
        $this->deferred=new Deferred($canceller);
        $this->loop=$loop;
    }
    
    /**
     * Configures the Processor: Must be overriden to configure the Processor
     */
    public function configure() {}
    
    /**
     * Transform the Message: Must be overriden on derived class
     * @param \Greicodex\ServiceBuz\MessageInterface $msg
     * @return \Greicodex\ServiceBuz\MessageInterface
     */
    public function process(MessageInterface &$msg) {}

    public function __get($name) {
        if(\in_array($name,  \get_class_methods($this))) {
            return array($this,$name);
        }
    }

    public function forwardTo(ProcessorInterface $nextProc) {
        $this->emit('processor.connect.begin',[$this,$nextProc]);
        
        try {
            $this->on('message',function($msg) use(&$nextProc) {
                var_dump('MessageDispatch');
                $nextProc->process($msg);
            });
            $nextProc->emit('processor.connect.end',[$nextProc,$this]);
            
        }catch(Exception $ie) {
            $e = new \Exception('Error connecting', 800041, $ie);
            $this->emit('processor.connect.error',[$e]);
        }
        
        return $nextProc;
    }
    
    public static function FactoryCreate($uri,LoopInterface $loop) {
        //$classname=static::class;
        $instance= new static($loop);
        $instance->params= parse_url($uri);
        $instance->configure();
        return $instance;
    }

}
