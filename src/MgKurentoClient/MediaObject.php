<?php

namespace MgKurentoClient;

interface MediaObject {
    
    public function getId();    
    public function getMediaPipeline();
    public function getParent();
    public function release();
    
}
