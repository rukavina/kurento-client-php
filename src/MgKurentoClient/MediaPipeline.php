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

class MediaPipeline extends MediaObject implements Interfaces\MediaPipeline {
    
    /**
     *
     * @var \MgKurentoClient\JsonRpc\Client;
     */
    private $jsonRpc = null;    
    
    function __construct(JsonRpc\Client $jsonRpc) {
        $this->jsonRpc = $jsonRpc;
        parent::__construct($this);
    }  
    
    public function getJsonRpc(){
        return $this->jsonRpc;
    }
    
}
