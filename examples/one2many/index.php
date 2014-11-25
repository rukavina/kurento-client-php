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
 * This CallHandler handler below will respond to all messages sent to /call (e.g. ws://localhost:8080/call)
 * 
 * first run WS server with php index.php
 */
class CallHandler extends WebSocketUriHandler {
    
    protected $loop = null;
    protected $viewers = array();
    
    /**
     *
     * @var array
     */
    protected $master = array(
        'id'    => null,
        'webRtcEndpoint'    => null
    );
    
    /**
     *
     * @var WebSocketTransportInterface 
     */
    protected $masterUser = null;
    
    /**
     *
     * @var \MgKurentoClient\KurentoClient
     */
    protected $client = null;
    
    /**
     *
     * @var \MgKurentoClient\Interfaces\MediaPipeline
     */
    protected $pipeline = null;
    protected $wsUrl = 'ws://127.0.0.1:8888/kurento';
    
    /**
     * 
     * @var \MgKurentoClient\WebRtcEndpoint
     */
    protected $webRtcEndpoint = null;
    
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
    
    protected function sendResponse(WebSocketTransportInterface $user, $id, $response, $message = '', $params = array()){
        $resultParams = array_merge(array(
            "id"        => $id,
            "response"  => $response,
            "message"   => $message
        ), $params);
        $user->sendString(json_encode($resultParams));        
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
            case 'master':
                $this->master($user, $message);
                break;
            case 'viewer':
                $this->viewer($user, $message);
                break;
            case 'stop':
                $this->stop($user);
                break;

            default:
                break;
        }
    }
    
    /* TODO handle on close */
    
    public function master(WebSocketTransportInterface $user, array $message){
        if(isset($this->master['id'])){
            return $this->sendResponse($user, 'masterResponse', 'rejected', 'Another user is currently acting as sender. Try again later ...');
        }
        //build pipeline
        $this->pipeline = $this->client->createMediaPipeline(function($pipeline, $success, $data) use ($user, $message){
            $this->pipelines[$user->getId()] = $pipeline;
            $this->master['id'] = $user->getId();
            $this->masterUser = $user;
            //build webRtcEndpoint
            $webRtcEndpoint = new \MgKurentoClient\WebRtcEndpoint($this->pipeline);
            $webRtcEndpoint->build(function($webRtcEndpoint, $success, $data) use ($user, $message){
                $this->master['webRtcEndpoint'] = $webRtcEndpoint;
                //process sdp offer
                $webRtcEndpoint->processOffer($message['sdpOffer'], function($success, $data) use ($user, $message){
                    return $this->sendResponse($user, 'masterResponse', 'accepted', '', array(
                        'sdpAnswer' => $data['value']
                    ));
                });
            });
        });  
    }
    
    public function viewer(WebSocketTransportInterface $user, array $message){
        if(!isset($this->master['id']) || !isset($this->master['webRtcEndpoint'])){
            return $this->sendResponse($user, 'viewerResponse', 'rejected', 'No active sender now. Become sender or . Try again later ...');
        }
        if(isset($this->viewers[$user->getId()])){
            return $this->sendResponse($user, 'viewerResponse', 'rejected', 'You are already viewing in this session. Use a different browser to add additional viewers.');
        }
        $this->viewers[$user->getId()] = array('user' => $user);
        //build webRtcEndpoint
        $webRtcEndpoint = new \MgKurentoClient\WebRtcEndpoint($this->pipeline);
        $webRtcEndpoint->build(function($webRtcEndpoint, $success, $data) use ($user, $message){
            $this->viewers[$user->getId()]['webRtcEndpoint'] = $webRtcEndpoint;
            $masterWebRtcEndpoint = $this->master['webRtcEndpoint'];
            $masterWebRtcEndpoint->connect($webRtcEndpoint, function($success, $data) use ($user, $message, $webRtcEndpoint){
                //process sdp offer
                $webRtcEndpoint->processOffer($message['sdpOffer'], function($success, $data) use ($user, $message){
                    return $this->sendResponse($user, 'viewerResponse', 'accepted', '', array(
                        'sdpAnswer' => $data['value']
                    ));
                });                
            });
        });        
    }
    
    public function stop(WebSocketTransportInterface $user){        
        if(isset($this->master['id']) && $this->master['id'] == $user->getId()){
            foreach ($this->viewers as $viewerId => $viewer) {
                $this->sendResponse($viewer['user'], 'stopCommunication', '');                
            }
            $this->viewers = array();
            $this->pipeline->release(function() use ($user){
                $this->pipeline = null;
                $this->master['id'] = null;
                $this->master['webRtcEndpoint'] = null;
                $this->masterUser = null;
            });             
        }
        if(isset($this->viewers[$user->getId()])){
            $webRtcEndpoint = $this->viewers[$user->getId()]['webRtcEndpoint'];
            $webRtcEndpoint->release(function() use ($user){
                unset($this->viewers[$user->getId()]);
            });             
        }
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
$router->addRoute('#^/call$#i', new CallHandler($logger, $loop));

// Bind the server
$server->bind();

// Start the event loop
$loop->run();
