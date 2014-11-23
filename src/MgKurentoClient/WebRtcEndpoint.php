<?php

namespace MgKurentoClient;

class WebRtcEndpoint extends MediaElement implements Interfaces\WebRtcEndpoint {
    
    public function generateOffer(){
        
    }
    public function getLocalSessionDescriptor(){
        
    }
    public function getRemoteSessionDescriptor(){
        
    }
    public function processAnswer($answer, $callback){
        
    }
    public function processOffer($offer, $callback){
        $this->remoteInvoke('processOffer', array('offer' => $offer), function($success, $data) use ($callback){
            $callback($success, $data);
        });        
    }
}
