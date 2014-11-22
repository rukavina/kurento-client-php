<?php

namespace MgKurentoClient\WebRtc;

class Client {
    
    /**
     *
     * @var \Devristo\Phpws\Client\WebSocket 
     */
    private $client = null;
    private $loop = null;
    private $logger = null;


    /**
     * private constructor
     */
    public function  __construct($websocketUrl, $loop)
    {
        $this->logger = new \Zend\Log\Logger();                
        $writer = new \Zend\Log\Writer\Stream("php://output");        
        $this->logger->addWriter($writer);        
        $this->loop = $loop;
        $this->client = new \Devristo\Phpws\Client\WebSocket($websocketUrl . '?encoding=text', $this->loop, $this->logger);
        
        //debug
        $this->client->on("request", function($headers){
            print_r("\nRequest object created!\n");
        });

        $this->client->on("handshake", function() {
            print_r("\nHandshake received!\n");
        });

        $this->client->on("connect", function(){
            print_r("\nConnected!\n");
        });

        $this->client->on("message", function($message){
            print_r("\nGot message: " . $message->getData());
        });        
      
    }       
    
    /**
     *
     * @param string $websocketUrl
     * @return \Devristo\Phpws\Client\WebSocket
     */
    public function open() {
        $this->client->open();
    }
    
    public function send($message){
        print_r("\nSending message: " . $message);
        $this->client->send($message);
    }
    
    public function onMessage($callback){
        $this->client->on("message", function($message) use ($callback){
            $callback($message->getData());
        });                
    }
    
    public function onConnect($callback){
        $this->client->on("connect", $callback);                
    }    

}
