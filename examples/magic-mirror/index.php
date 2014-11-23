<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once("../../vendor/autoload.php");

use Devristo\Phpws\Framing\WebSocketFrame;
use Devristo\Phpws\Framing\WebSocketOpcode;
use Devristo\Phpws\Messaging\WebSocketMessageInterface;
use Devristo\Phpws\Protocol\WebSocketTransportInterface;
use Devristo\Phpws\Server\IWebSocketServerObserver;
use Devristo\Phpws\Server\UriHandler\WebSocketUriHandler;
use Devristo\Phpws\Server\WebSocketServer;

/**
 * This MirrorHandler handler below will respond to all messages sent to /magicmirror (e.g. ws://localhost:8080/magicmirror)
 * 
 * first run WS server with php index.php
 */
class MirrorHandler extends WebSocketUriHandler {
    
    protected $loop = null;
    protected $pipelines = array();
    protected $client = null;
    protected $wsUrl = 'ws://127.0.0.1:8888/kurento';
    
    function __construct($logger, $loop) {
        $this->loop = $loop;
        parent::__construct($logger);
        $this->client = \MgKurentoClient\KurentoClient::create($this->wsUrl, $this->loop, $this->logger, function($client){
            $this->logger->notice("KurentoClient created");
        });
    }

     /**
     * New client connected
     *
     * @param WebSocketTransportInterface $user
     */
    public function onConnect(WebSocketTransportInterface $user){
        /*foreach($this->getConnections() as $client){
            $client->sendString("User {$user->getId()} joined the chat: ");
        }*/
    }

    /**
     * New message
     *
     * @param WebSocketTransportInterface $user
     * @param WebSocketMessageInterface $msg
     */
    public function onMessage(WebSocketTransportInterface $user, WebSocketMessageInterface $msg) {
        $this->logger->notice("New message:  " . $msg->getData() . " from User: " . $user->getId());
        $message = json_decode($msg->getData(), true);
        
        switch ($message['id']) {
            case 'start':
                $this->start($user, $message);
                break;
            case 'stop':
                if(isset($this->pipelines[$user->getId()])){
                    /* @var $pipeline \MgKurentoClient\MediaPipeline */
                    $pipeline = $this->pipelines[$user->getId()];
                    $pipeline->release();
                    unset($this->pipelines[$user->getId()]);
                }
                break;

            default:
                break;
        }
    }
    
    public function start(WebSocketTransportInterface $user, array $message){
        $this->client->createMediaPipeline(function($pipeline, $success, $data) use ($user, $message){
            $this->pipelines[$user->getId()] = $pipeline;
            $mediaBuilder = new \MgKurentoClient\MediaElementBuilder($pipeline);
            $mediaBuilder->build(function($webRtcEndpoint, $success, $data) use ($user, $message){
                $webRtcEndpoint->connect($webRtcEndpoint, function($success, $data) use ($webRtcEndpoint, $user, $message){
                    /* @var $webRtcEndpoint \MgKurentoClient\WebRtcEndpoint */
                    $webRtcEndpoint->processOffer($message['sdpOffer'], function($success, $data) use($user, $message){
                        $user->sendString($data['value']);
                    });
                });
            }, 'WebRtcEndpoint');
        });  
    }
}


$loop = \React\EventLoop\Factory::create();

// Create a logger which writes everything to the STDOUT
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

// Create a WebSocket server
$server = new WebSocketServer("tcp://0.0.0.0:8080", $loop, $logger);

// Create a router which transfers all /chat connections to the ChatHandler class
$router = new \Devristo\Phpws\Server\UriHandler\ClientRouter($server, $logger);
// route /chat url
$router->addRoute('#^/magicmirror$#i', new MirrorHandler($logger, $loop));

// Bind the server
$server->bind();

// Start the event loop
$loop->run();
