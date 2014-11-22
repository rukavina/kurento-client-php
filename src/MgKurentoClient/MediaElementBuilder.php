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
        $className = '\MgKurentoClient\Impl\\' . $className;
        if(!class_exists($className)){
            throw new \Exception($className . ' does not exist!');
            return false;
        }
        /* @var $mediaElement \MgKurentoClient\MediaElement */
        $mediaElement = new $className($this->pipeline);
        $mediaElement->create($params, function($success, $data) use ($mediaElement, $callback){
            $callback($mediaElement, $success, $data);
        });
    }
}
