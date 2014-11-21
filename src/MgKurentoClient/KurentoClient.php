<?php

namespace MgKurentoClient;

/**
 * Factory to create {MediaPipeline} in the media server.
 *
 * @author Milan Rukavina
 */
class KurentoClient {
    
    /**
     *
     * @var KurentoClient 
     */
    protected static $instance;
    
    /**
     *
     * @var \MgKurentoClient\JsonRpc\Client;
     */
    private $jsonRpc = null;
    
    private function __construct($websocketUrl, $loop) {
        $this->jsonRpc = new \MgKurentoClient\JsonRpc\Client($websocketUrl, $loop);
    }

    public static function create($websocketUrl, $loop) {
        if(!isset(self::$instance)){
            self::$instance = new self($websocketUrl, $loop);
        }
        return self::$instance;
    }

    /**
     * Creates a new {MediaPipeline} in the media server
     *
     * @return \MgKurentoClient\MediaPipeline
     */
    public function createMediaPipeline() {
        return new \MgKurentoClient\Impl\MediaPipeline($this->jsonRpc);
    }

}