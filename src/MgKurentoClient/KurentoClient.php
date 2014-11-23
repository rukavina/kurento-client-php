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
     * @var JsonRpc\Client;
     */
    private $jsonRpc = null;
    
    /**
     *
     * @var \MgKurentoClient\MediaPipeline 
     */
    private $pipeline = null;
    
    private $logger = null;
    
    private function __construct($websocketUrl, $loop, $logger, callable $callback) {
        $this->logger = $logger;
        $this->jsonRpc = new JsonRpc\Client($websocketUrl, $loop, $this->logger, $callback);
    }
    
    /**
     * Creates Client object
     * 
     * @param string $websocketUrl
     * @param LibEventLoop|LibEvLoop|ExtEventLoop|StreamSelectLoop $loop
     * @param \Zend\Log\Logger $logger
     * 
     * @return KurentoClient 
     */
    public static function create($websocketUrl, $loop, $logger, callable $callback) {
        if(!isset(self::$instance)){
            self::$instance = new self($websocketUrl, $loop, $logger, function() use ($callback){
                $callback(self::$instance);
            });
        }
        return self::$instance;
    }

    /**
     * Creates a new {MediaPipeline} in the media server
     * 
     * @param callable $callback
     *
     * @return Interfaces\MediaPipeline
     */
    public function createMediaPipeline(callable $callback) {        
        $this->pipeline = new MediaPipeline($this->jsonRpc);        
        $this->pipeline->build(function($success, $data) use ($callback){
            $callback($this->pipeline, $success, $data);
        });
        return $this->pipeline;
    }

}