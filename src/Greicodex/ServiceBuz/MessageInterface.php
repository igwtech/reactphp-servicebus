<?php

namespace Greicodex\ServiceBuz;

/**
 *
 * @author javier
 */
interface MessageInterface {    
    /**
     * @return array Headers
     */
    public function getHeaders();
    
    /**
     * 
     * @param array $assoc_headers
     */
    public function setHeaders($assoc_headers);
    
    /**
     * @return mixed Body Data
     */
    public function getBody();
    
    /**
     * @param mixed Body Data
     */
    public function setBody($body);
    
    /**
     * @return mixed Unique id
     */
    public function getId();
    
    /**
     * @return string Type Description
     */
    public function getType();
    
    /**
     * 
     * @param string $key
     */
    public function getHeader($key);
    
    /**
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setHeader($key,$value);    

    /**
     * 
     * @param string $key
     * @param mixed $value
     */
    public function addHeader($key,$value);
    
    /**
     * 
     * @param string $key
     */
    public function removeHeader($key);
    
}
