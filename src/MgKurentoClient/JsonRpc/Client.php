<?php

namespace MgKurentoClient\JsonRpc;

class Client{
    protected $wsClient;
    protected $id = 0;
    protected $sessionId = null;
    protected $callbacks = array();


    protected function send($method, $params, $callback){
        $this->id++;
        if(isset($this->sessionId)){
            $params['sessionId'] = $this->sessionId;
        }
        
        $data = array(
            "jsonrpc"   =>  "2.0",
            "id"         =>   $this->id,
            "method"    => $method,
            "params"    => $params
        );
        $this->wsClient->publish($data);
        $this->callbacks[$this->id] = $callback;
    }
    
    public function receive($data){
        //set sesstion 
        if(isset($data['result']['sessionId'])){
            $this->sessionId = $data['result']['sessionId'];
        }
        if(isset($data['result']) && isset($data['id']) && isset($this->callbacks[$data['id']])){
            $callback = $this->callbacks[$data['id']];
            $callback(true, $data['result']);
            unset($this->callbacks[$data['id']]);
            return;
        }
        if(isset($data['error']) && isset($data['id']) && isset($this->callbacks[$data['id']])){
            $callback = $this->callbacks[$data['id']];
            $callback(false, $data['error']);
            unset($this->callbacks[$data['id']]);
            return;
        }
        
        throw new \MgKurentoClient\JsonRpc\Exception('Json callback not found');
    }    
    
    public function sendCreate($type, $creationParams, $callback){
        return $this->send('create', array(
            'type'  => $type,
            'creationParams'    => $creationParams
        ), $callback);
    }
    
    public function sendInvoke($object, $operation, $operationParams, $callback){
        return $this->send('invoke', array(
            'object'  => $object,
            'operation' => $operation,
            'operationParams'    => $operationParams
        ), $callback);
    }
    
    public function sendRelease($object, $callback){
        return $this->send('release', array(
            'object'  => $object
        ), $callback);
    }
    
    public function sendSubscribe($object, $type, $callback){
        return $this->send('subscribe', array(
            'object'  => $object,
            'type'      => $type
        ), $callback);
    }
    
    public function sendUnsubscribe($subscription, $callback){
        return $this->send('unsubscribe', array(
            'subscription'  => $subscription
        ), $callback);
    }    
}
