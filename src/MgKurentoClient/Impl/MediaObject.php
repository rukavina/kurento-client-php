<?php

namespace MgKurentoClient\Impl;

class MediaObject implements \MgKurentoClient\MediaObject {
    
    protected $pipeline = null;
    protected $id = null;
    
    function __construct(\MgKurentoClient\MediaPipeline $pipeline) {
        $this->pipeline = $pipeline;
        $this->id = uniqid();
    }

    
    public function getId(){
        return $this->id;
    }    
    
    public function getMediaPipeline(){
        return $this->pipeline;
        
    }
    
    public function getParent(){
        
    }
    
    public function release(){
        
    }
    
}
