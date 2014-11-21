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
    private function  __construct($websocketUrl, $loop)
    {
        $this->logger = new \Zend\Log\Logger();                
        $writer = new Zend\Log\Writer\Stream("php://output");        
        $this->logger->addWriter($writer);        
        $this->loop = $loop;
        $this->client = new \Devristo\Phpws\Client\WebSocket($websocketUrl . '?encoding=text', $this->loop, $this->logger);
      
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
        $this->client->send($message);
    }
    
    public function onMessage($callback){
        $this->client->on("message", function($message){
            $callback($message->getData());
        });                
    }

}
