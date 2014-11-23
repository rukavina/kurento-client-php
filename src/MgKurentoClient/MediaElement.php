<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient;

class MediaElement extends MediaObject implements Interfaces\MediaElement {
    
    protected $sinks = array();
    protected $sources = array();
        
    public function connect(Interfaces\MediaElement $sink, $callback){
        $this->remoteInvoke('connect', array('sink' => $sink->getId()), function($success, $data) use ($callback, $sink){
            if($success){
                $this->sinks[] = $sink;
                $sink->addSource($this);                
            }
            $callback($success, $data);
        });
    }
    
    public function addSource(Interfaces\MediaElement $source){
        $this->sources[] = $source;
    }
    
    public function getMediaSinks(){
        return $this->sinks;
    }
    
    public function getMediaSrcs(){
        return $this->sources;
    }
    
}
