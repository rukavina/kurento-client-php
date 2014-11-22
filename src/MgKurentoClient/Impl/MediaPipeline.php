<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\Impl;

class MediaPipeline extends MediaObject implements \MgKurentoClient\MediaPipeline {
    protected $remoteType = 'MediaPipeline';
    
    /**
     *
     * @var \MgKurentoClient\JsonRpc\Client;
     */
    private $jsonRpc = null;    
    
    function __construct(\MgKurentoClient\JsonRpc\Client $jsonRpc) {
        $this->jsonRpc = $jsonRpc;
        parent::__construct($this);
    }  
    
    public function getJsonRpc(){
        return $this->jsonRpc;
    }
    
}
