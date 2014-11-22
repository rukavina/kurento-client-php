<?php

namespace MgKurentoClient\Impl;

class WebRtcEndpoint extends MediaElement implements \MgKurentoClient\WebRtcEndpoint {
    protected $remoteType = 'WebRtcEndpoint';
    
    public function generateOffer(){
        
    }
    public function getLocalSessionDescriptor(){
        
    }
    public function getRemoteSessionDescriptor(){
        
    }
    public function processAnswer($answer, $callback){
        
    }
    public function processOffer($offer, $callback){
        $this->remoteInvoke('processOffer', array('offer' => $offer), function($success, $data){
            $callback($success, $data);
        });        
    }
}
