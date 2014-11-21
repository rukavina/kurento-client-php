<?php

namespace MgKurentoClient\Impl;

class MediaElement extends MediaObject implements \MgKurentoClient\MediaElement {
    
    protected $sinks = array();
    protected $sources = array();
    
    public function connect(\MgKurentoClient\MediaElement $sink){
        $this->remoteInvoke('connect', array('sink' => $sink->getId()), function($success, $data) use ($sink){
            if($success){
                $this->sinks[] = $sink;
                $sink->addSource($this);                
            }
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
