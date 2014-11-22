<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\WebRtc;

/**
 * Websocket transport layer implementation
 * 
 * @author Milan Rukavina 
 */
class Client {
    
    /**
     *
     * @var \Devristo\Phpws\Client\WebSocket 
     */
    private $client = null;
    private $loop = null;
    private $logger = null;


    /**
     *
     * Constructor
     */
    public function  __construct($websocketUrl, $loop, $logger)
    {
        $this->logger = $logger;
        $this->loop = $loop;
        $this->client = new \Devristo\Phpws\Client\WebSocket($websocketUrl . '?encoding=text', $this->loop, $this->logger);
        
        //debug
        $this->client->on("request", function($headers){            
            $this->logger->notice("\nRequest object created!\n");
        });

        $this->client->on("handshake", function() {
            $this->logger->notice("\nHandshake received!\n");
        });

        $this->client->on("connect", function(){
            $this->logger->notice("\nConnected!\n");
        });

        $this->client->on("message", function($message){
            $this->logger->notice("\nGot message: " . $message->getData());
        });        
      
    }       
    
    /**
     * Open WS connections 
     */
    public function open() {
        $this->client->open();
    }
    
    /**
     * Send message
     * 
     * @param string $message 
     */
    public function send($message){
        $this->logger->notice("\nSending message: " . $message);
        $this->client->send($message);
    }
    
    /**
     * On message received callback
     * 
     * @param callable $callback 
     */
    public function onMessage(callable $callback){
        $this->client->on("message", function($message) use ($callback){
            $callback($message->getData());
        });                
    }
    
    /**
     * On connect callback
     * 
     * @param callable $callback 
     */
    public function onConnect(callable $callback){
        $this->client->on("connect", $callback);                
    }    

}
