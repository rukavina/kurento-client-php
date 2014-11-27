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

class MediaObject implements Interfaces\MediaObject {
    
    protected $pipeline = null;
    protected $id = null;
    protected $subscriptions = array();


    function __construct(Interfaces\MediaPipeline $pipeline) {
        $this->pipeline = $pipeline;
    }

    
    public function getId(){
        return $this->id;
    }    
    
    public function getMediaPipeline(){
        return $this->pipeline;
        
    }
    
    public function getParent(){
        
    }
    
    public function release(callable $callback){
        $this->remoteRelease($callback);        
    }
    
    protected function getRemoteTypeName(){
        $fullName = get_class($this);
        $parts = explode("\\", $fullName);
        return $parts[count($parts) - 1];
    }
    
    public function build(callable $callback, array $params = array()){
        $this->remoteCreate($this->getRemoteTypeName(), function($success, $data) use($callback){
            $callback($this, $success, $data);
        }, $params);
    }     
    
    public function remoteCreate($remoteType, callable $callback, array $params = array()){
        $localParams = ($this->pipeline == $this)? array(): array('mediaPipeline'  => $this->pipeline->getId());        
        $this->pipeline->getJsonRpc()->sendCreate($remoteType, array_merge($localParams, $params), function($success, $data) use($callback){
            if($success && isset($data['value'])){
                $this->id = $data['value'];
            }
            $callback($success, $data);
        });
    }    
    
    public function remoteInvoke($operation, $operationParams, callable $callback){
        $this->pipeline->getJsonRpc()->sendInvoke($this->getId(), $operation, $operationParams, $callback);
    }
    
    public function remoteRelease(callable $callback){
        $this->pipeline->getJsonRpc()->sendRelease($this->getId(), $callback);
    }
    
    protected function remoteSubscribe($type, $onEvent, callable $callback){
        $this->pipeline->getJsonRpc()->sendSubscribe($this->getId(), $type, $onEvent, $callback);
    }
    
    public function remoteUnsubscribe($subscription, callable $callback){
        $this->pipeline->getJsonRpc()->sendUnsubscribe($subscription, $callback);
    }    
    
}
