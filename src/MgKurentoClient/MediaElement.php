<?php

namespace MgKurentoClient;

interface MediaElement extends MediaObject {    
    public function connect(MediaElement $sink, $callback);
    public function addSource(MediaElement $source);
    public function getMediaSinks();
    public function getMediaSrcs();
    
    
}
