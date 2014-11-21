<?php

namespace MgKurentoClient\WebRtc;

class Client {
    
    private static $instance;
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
    private function  __construct()
    {                
        $this->logger = new \Zend\Log\Logger();                
        $writer = new Zend\Log\Writer\Stream("php://output");        
        $this->logger->addWriter($writer);
        
        $this->loop = \React\EventLoop\Factory::create();
    }    
    
    /**
     *
     * @return \MgKurentoClient\WebRtc\Client
     */
    public static function getInstance(){        
        if(self::$instance === null)
        {            
            self::$instance = new self();
        }        
        return self::$instance;        
    }
    
    /**
     *
     * @param string $websocketUrl
     * @return \Devristo\Phpws\Client\WebSocket
     */
    public function initClient($websocketUrl, $loop) {
        $this->loop = $loop;
        try {
            $this->client = new \Devristo\Phpws\Client\WebSocket($websocketUrl, $this->loop, $this->logger);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
                
        $this->client->open();        
        return $this->client;
    }
    
    /**
     *
     * @return \Devristo\Phpws\Client\WebSocket
     */
    public function getClient(){
        return $this->client;
    }

}
