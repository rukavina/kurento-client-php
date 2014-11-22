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
    
    /**
     *
     * @var \MgKurentoClient\MediaPipeline 
     */
    private $pipeline = null;
    
    private function __construct($websocketUrl, $loop, $callback) {
        $this->jsonRpc = new \MgKurentoClient\JsonRpc\Client($websocketUrl, $loop, $callback);
    }
    
    /**
     *
     * @param string $websocketUrl
     * @param LibEventLoop|LibEvLoop|ExtEventLoop|StreamSelectLoop $loop
     * @return KurentoClient 
     */
    public static function create($websocketUrl, $loop, $callback) {
        if(!isset(self::$instance)){
            self::$instance = new self($websocketUrl, $loop, function() use ($callback){
                $callback(self::$instance);
            });
        }
        return self::$instance;
    }

    /**
     * Creates a new {MediaPipeline} in the media server
     * 
     * @param mixed $callback
     *
     * @return \MgKurentoClient\MediaPipeline
     */
    public function createMediaPipeline($callback) {        
        $this->pipeline = new \MgKurentoClient\Impl\MediaPipeline($this->jsonRpc);        
        $this->pipeline->create(array(), function($success, $data) use ($callback){
            $callback($this->pipeline, $success, $data);
        });
        return $this->pipeline;
    }

}