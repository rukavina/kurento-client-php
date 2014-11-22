<?php

namespace MgKurentoClient;

class MediaElementBuilder {
    
    /**
     * \MgKurentoClient\MediaPipeline
     * @var type 
     */
    protected $pipeline = null;
    
    function __construct(\MgKurentoClient\MediaPipeline $pipeline) {
        $this->pipeline = $pipeline;
    }

    public function build($callback, $className, $params = array()){
        $className = '\MgKurentoClient\MediaPipeline' . $className;
        if(!class_exists($className)){
            return false;
        }
        /* @var $mediaElement \MgKurentoClient\MediaElement */
        $mediaElement = new $className($this->pipeline);
        $mediaElement->create(function($success, $data) use ($callback){
            $callback($mediaElement, $success, $data);
        });
    }
}
