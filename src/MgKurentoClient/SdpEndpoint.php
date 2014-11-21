<?php

namespace MgKurentoClient;

interface SdpEndpoint extends SessionEndpoint {
    public function generateOffer();
    public function getLocalSessionDescriptor();    
    public function getRemoteSessionDescriptor();
    public function processAnswer($answer);
    public function processOffer($offer);
    
    
}
