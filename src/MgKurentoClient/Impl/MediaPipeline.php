<?php

namespace MgKurentoClient\Impl;

class MediaPipeline extends MediaObject implements \MgKurentoClient\MediaPipeline {    
    
    /**
     *
     * @var \MgKurentoClient\JsonRpc\Client;
     */
    private $jsonRpc = null;    
    
    function __construct(\MgKurentoClient\JsonRpc\Client $jsonRpc) {
        $this->jsonRpc = $jsonRpc;
        parent::__construct($this);
    }  
    
    public function getJsonRpc(){
        return $this->jsonRpc;
    }
    
}
