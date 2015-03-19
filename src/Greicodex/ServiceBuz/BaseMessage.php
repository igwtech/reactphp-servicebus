<?php
namespace Greicodex\ServiceBuz;
use Greicodex\ServiceBuz\MessageInterface;
/**
 * Description of BaseMessage
 *
 * @author javier
 */
class BaseMessage implements MessageInterface {
    protected $body;
    protected $headers;
    protected $id;
    protected $type;
            
    const MSG_UNKNOWN='*/*';
    
    function __construct() {
        $this->headers=array();
        $this->body=null;
        $this->type=BaseMessage::MSG_UNKNOWN;
        
        $this->id=  uniqid();
    }

    public function getBody() {
        return $this->body;
    }

    public function getHeader($key) {
        return $this->headers[$key];
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }

    public function setBody($body) {
        return $this->body=$body;
    }

    public function setHeader($key, $value) {
        return $this->headers[$key]=$value;
    }

    public function setHeaders(array $assoc_headers) {
        return $this->headers=$assoc_headers;
    }

    public function addHeader($key, $value) {
        $this->headers[$key]=$value;
    }

    public function removeHeader($key) {
        unset($this->headers[$key]);
    }

}
