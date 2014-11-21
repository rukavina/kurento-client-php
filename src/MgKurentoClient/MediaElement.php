<?php

namespace MgKurentoClient;

interface MediaElement extends MediaObject {
    
    public function connect(MediaElement $sink);    
    public function getMediaSinks();
    public function getMediaSrcs();
    
    
}
