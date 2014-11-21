<?php

namespace MgKurentoClient\JsonRpc;

class Client{
    /**
     *
     * @var \MgKurentoClient\WebRtc\Client 
     */
    protected $wsClient;
    protected $id = 0;
    protected $sessionId = null;
    protected $callbacks = array();
    protected $subscriptions = array();
    
    function __construct($websocketUrl, $loop) {
        $this->wsClient = new \MgKurentoClient\WebRtc\Client($websocketUrl, $loop);
        $this->wsClient->open();
        $this->wsClient->onMessage(function(){
            $this->receive(json_decode($data, true));
        });
    }

    
    protected function send($method, $params, $callback){
        $this->id++;
        if(isset($this->sessionId)){
            $params['sessionId'] = $this->sessionId;
        }
        
        $data = array(
            "jsonrpc"   => "2.0",
            "id"         => $this->id,
            "method"    => $method,
            "params"    => $params
        );
        $this->wsClient->send(json_encode($data));
        $this->callbacks[$this->id] = $callback;
    }
    
    public function receive($data){
        //set sesstion 
        if(isset($data['result']['sessionId'])){
            $this->sessionId = $data['result']['sessionId'];
        }
        //onEvent?
        if(isset($data['method']) && $data['method'] == 'onEvent'){
            if(isset($this->subscriptions[$data['params']['value']['subscription']])){
                $onEvent = $this->subscriptions[$data['params']['value']['subscription']];
                $onEvent($data);
            }
            return;
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
    
    public function sendSubscribe($object, $type, $onEvent, $callback){
        return $this->send('subscribe', array(
            'object'  => $object,
            'type'      => $type
        ), function($success, $data) use($onEvent){
            if(!$success){                
                return false;                
            }
            $this->subscriptions[$data['value']] = $onEvent;
            $callback($success, $data);            
        });
    }
    
    public function sendUnsubscribe($subscription, $callback){
        return $this->send('unsubscribe', array(
            'subscription'  => $subscription
        ), function($success, $data) use ($subscription){
            if(!$success){                
                return false;                
            }            
            unset($this->subscriptions[$subscription]);
            $callback($success, $data);
        });
    }    
}
