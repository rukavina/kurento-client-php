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
    
    /**
     * 
     * @var \MgKurentoClient\WebRtcEndpoint
     */
    protected $webRtcEndpoint = null;
    /**
     *
     * @var \MgKurentoClient\FaceOverlayFilter
     */
    protected $faceOverlayFilter= null;
    
    function __construct($logger, $loop) {
        $this->loop = $loop;
        parent::__construct($logger);
        $this->client = \MgKurentoClient\KurentoClient::create($this->wsUrl, $this->loop, $this->logger, function($client){
            $this->logger->notice("KurentoClient created");
        });
    }

     /**
     * A client disconnected
     *
     * @param WebSocketTransportInterface $user
     */
    public function onDisconnect(WebSocketTransportInterface $user){
        $this->stop($user);
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
                $this->stop($user);
                break;

            default:
                break;
        }
    }
    
    public function stop(WebSocketTransportInterface $user){
        if(isset($this->pipelines[$user->getId()])){
            /* @var $pipeline \MgKurentoClient\MediaPipeline */
            $pipeline = $this->pipelines[$user->getId()];
            $pipeline->release(function() use ($user){
                unset($this->pipelines[$user->getId()]);
            });                    
        }        
    }
    
    public function start(WebSocketTransportInterface $user, array $message){
        //build pipeline
        $this->client->createMediaPipeline(function($pipeline, $success, $data) use ($user, $message){
            $this->pipelines[$user->getId()] = $pipeline;
            //build webRtcEndpoint
            $this->webRtcEndpoint = new \MgKurentoClient\WebRtcEndpoint($pipeline);
            $this->webRtcEndpoint->build(function($webRtcEndpoint, $success, $data) use ($user, $message){
                //build faceOverlayFilter
                $this->faceOverlayFilter = new \MgKurentoClient\FaceOverlayFilter($this->pipelines[$user->getId()]);
                $this->faceOverlayFilter->build(function($faceOverlayFilter, $success, $data) use($user, $message){
                    //set overlay
                    $this->faceOverlayFilter->setOverlayedImage("http://files.kurento.org/imgs/mario-wings.png", -0.35, -1.2, 1.6, 1.6, function($success, $data) use($user, $message){
                        //connect webRtcEndpoint to faceOverlayFilter
                        $this->webRtcEndpoint->connect($this->faceOverlayFilter, function($success, $data) use ($user, $message){
                            //connect faceOverlayFilter to webRtcEndpoint
                            $this->faceOverlayFilter->connect($this->webRtcEndpoint, function($success, $data) use ($user, $message){
                                //process sdp offer
                                $this->webRtcEndpoint->processOffer($message['sdpOffer'], function($success, $data) use ($user, $message){
                                    $user->sendString(json_encode(array(
                                        "id"            => "startResponse",
                                        "sdpAnswer" => $data['value']
                                    )));
                                });
                            });
                        });
                    });
                });
            });
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
