<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\JsonRpc;

/**
 * JSON RPC implementation
 * 
 * @author Milan Rukavina
 *  
 */
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
    protected $logger = null;
    
    function __construct($websocketUrl, $loop, $logger, $callback) {
        $this->logger = $logger;
        $this->wsClient = new \MgKurentoClient\WebRtc\Client($websocketUrl, $loop, $this->logger);
        $this->wsClient->open();
        $this->wsClient->onMessage(function($data){
            $this->receive(json_decode($data, true));
        });
        $this->wsClient->onConnect($callback);        
    }

    /**
     * Send method
     * 
     * @param string $method
     * @param array $params
     * @param callable $callback 
     */
    protected function send($method, $params, callable $callback){        
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
        $this->wsClient->send(json_encode($data, JSON_UNESCAPED_SLASHES));
        $this->callbacks[$this->id] = $callback;
    }
    
    /**
     * Receive data
     * 
     * @param array $data
     * @return mixed
     * @throws \MgKurentoClient\JsonRpc\Exception 
     */
    public function receive($data){
        //set sesstion 
        if(isset($data['result']['sessionId'])){
            $this->sessionId = $data['result']['sessionId'];
        }
        //onEvent?
        if(isset($data['method']) && $data['method'] == 'onEvent'){
            if(isset($this->subscriptions[$data['params']['value']['type']])){
                $onEvent = $this->subscriptions[$data['params']['value']['type']];
                $onEvent($data);
            }
            return;
        }
        if(array_key_exists('result',$data) && isset($data['id']) && isset($this->callbacks[$data['id']])){
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
    
    /**
     * Create method
     * 
     * @param string $type
     * @param array $creationParams
     * @param callable $callback
     * @return mixed 
     */
    public function sendCreate($type, $creationParams, callable $callback){        
        $message = array(
            'type'  => $type            
        );
        if(isset($creationParams) && count($creationParams)){
            $message['constructorParams'] = $creationParams;
        }
        return $this->send('create', $message, $callback);
    }
    
    /**
     * Invoke method
     * 
     * @param string $object
     * @param string $operation
     * @param array $operationParams
     * @param callable $callback
     * @return mixed 
     */
    public function sendInvoke($object, $operation, $operationParams, callable $callback){
        return $this->send('invoke', array(
            'object'  => $object,
            'operation' => $operation,
            'operationParams'    => $operationParams
        ), $callback);
    }
    
    /**
     * Release method
     * 
     * @param string $object
     * @param callable $callback
     * @return type 
     */
    public function sendRelease($object, callable $callback){
        return $this->send('release', array(
            'object'  => $object
        ), $callback);
    }
    
    /**
     * Subscribe method
     * 
     * @param string $object
     * @param string $type
     * @param string $onEvent
     * @param callable $callback
     * @return mixed 
     */
    public function sendSubscribe($object, $type, $onEvent, callable $callback){
        return $this->send('subscribe', array(
            'object'  => $object,
            'type'      => $type
        ), function($success, $data) use($onEvent, $callback){
            if(!$success){                
                return false;                
            }
            $this->subscriptions[$data['value']] = $onEvent;
            $callback($success, $data);            
        });
    }
    
    /**
     * Unsubscribe method
     * 
     * @param string $subscription
     * @param callable $callback
     * @return mixed 
     */
    public function sendUnsubscribe($subscription, callable $callback){
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
