<?php

namespace MgKurentoClient;

interface SdpEndpoint extends SessionEndpoint {
    public function generateOffer();
    public function getLocalSessionDescriptor();    
    public function getRemoteSessionDescriptor();
    public function processAnswer($answer, $callback);
    public function processOffer($offer, $callback);
    
    
}
