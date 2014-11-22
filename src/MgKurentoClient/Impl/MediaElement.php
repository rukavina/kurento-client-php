<?php

namespace MgKurentoClient\Impl;

class MediaElement extends MediaObject implements \MgKurentoClient\MediaElement {
    
    protected $sinks = array();
    protected $sources = array();
        
    public function connect(\MgKurentoClient\MediaElement $sink, $callback){
        $this->remoteInvoke('connect', array('sink' => $sink->getId()), function($success, $data) use ($callback, $sink){
            if($success){
                $this->sinks[] = $sink;
                $sink->addSource($this);                
            }
            $callback($success, $data);
        });
    }
    
    public function addSource(\MgKurentoClient\MediaElement $source){
        $this->sources[] = $source;
    }
    
    public function getMediaSinks(){
        return $this->sinks;
    }
    
    public function getMediaSrcs(){
        return $this->sources;
    }
    
}
