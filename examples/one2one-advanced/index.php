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
    protected $registry = array();
    protected $pipelines = array();
    
    
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
     * @param WebSocketTransportInterface $session
     */
    public function onDisconnect(WebSocketTransportInterface $session){
        unset($this->registry[$session->getId()]);
    }    
    
    protected function sendResponse(WebSocketTransportInterface $session, $id, $response, $message = '', $params = array()){
        $resultParams = array_merge(array(
            "id"        => $id,
            "response"  => $response,
            "message"   => $message
        ), $params);
        $session->sendString(json_encode($resultParams, JSON_UNESCAPED_SLASHES));        
    }

    /**
     * New message
     *
     * @param WebSocketTransportInterface $session
     * @param WebSocketMessageInterface $msg
     */
    public function onMessage(WebSocketTransportInterface $session, WebSocketMessageInterface $msg) {        
        $message = json_decode($msg->getData(), true);
        /* @var $user UserSession */
        $user = null;
        if(isset($this->registry[$session->getId()])){
            $user = $this->registry[$session->getId()];
            $this->logger->notice("message:  " . $msg->getData() . " from User: " . $user->getName());
        }
        else{
            $this->logger->notice("message:  " . $msg->getData() . " from new User");
        }
        
        switch ($message['id']) {
            case 'register':
                $this->register($session, $message);
                break;
            case 'call':
                $this->call($user, $message);
                break;
            case 'incomingCallResponse':
                $this->incomingCallResponse($user, $message);
                break;
            case 'play':
                $this->play($session, $message);
            case 'stop':
                $this->stop($session);
                break;

            default:
                break;
        }
    }
    
    /**
     * Name exists
     * 
     * @param string $name
     * @return boolean 
     */
    protected function registryNameExists($name){
        return $this->registryGetByName($name) !== false;
    }
    
    /**
     * Get user
     * 
     * @param string $name
     * @return boolean|UserSession 
     */
    protected function registryGetByName($name){
        /* @var $user UserSession */
        foreach ($this->registry as $sessionId => $user) {
            if($name == $user->getName()){
                return $this->registry[$sessionId];
            }
        }
        return false;
    }    
    
    public function register(WebSocketTransportInterface $session, array $message){
        $responseMsg = "accepted";
        if (!isset($message['name']) || $message['name'] == '') {
            $responseMsg = "rejected: empty user name";
        } elseif ($this->registryNameExists($message['name'])) {
            $responseMsg = "rejected: user '" . $message['name'] . "' already registered";
        } else {
            $user = new UserSession($session, $message['name']);
            $this->registry[$session->getId()] = $user;            
        }        
        return $this->sendResponse($session, 'resgisterResponse', $responseMsg);
    }
    
    public function call(UserSession $caller, array $message){
        $to = $message['to'];
        $from = $message['from'];

        if ($this->registryNameExists($to)) {
            $callee = $this->registryGetByName($to);
            $callee->setCallingFrom($from);
            
            $caller->setSdpOffer($message["sdpOffer"]);
            $caller->setCallingTo($to);            

            return $this->sendResponse($callee->getSession(), 'incomingCall', '', '', array('from' => $from));
        } else {
            
            return $this->sendResponse($caller->getSession(), 'callResponse', "rejected: user '" . $to . "' is not registered");
        }                
    }
    
    public function incomingCallResponse(UserSession $callee, array $message){
        $callResponse = $message['callResponse'];
        $caller = $this->registryGetByName($message['from']);

        if ("accept" == $callResponse) {
            $this->logger->notice("Accepted call from:  " . $message['from'] . " from User: " . $callee->getName());
            
            $pipeline = null;
            $callee->setSdpOffer($message["sdpOffer"]);

            try {
                $pipeline = new CallMediaPipeline($this->client, $caller->getName(), $callee->getName(), function($pipeline) use ($message, $callee, $caller) {
                    $this->pipelines[$caller->getSession()->getId()] = $pipeline;
                    $this->pipelines[$callee->getSession()->getId()] = $pipeline;
                    
                    $pipeline->generateSdpAnswerForCallee($callee->getSdpOffer(), function ($success, $data) use($pipeline, $caller, $callee){
                        $calleeSdpAnswer = $data['value'];
                        $pipeline->generateSdpAnswerForCaller($caller->getSdpOffer(), function ($success, $data) use ($pipeline, $calleeSdpAnswer, $caller, $callee){
                            //start recoding imidiatelly
                            $pipeline->record(function($success, $data){});
                            $callerSdpAnswer = $data['value'];
                            $this->sendResponse($callee->getSession(), 'startCommunication', '', '', array('sdpAnswer' => $calleeSdpAnswer));
                            $this->sendResponse($caller->getSession(), 'callResponse', 'accepted', '', array('sdpAnswer' => $callerSdpAnswer));                        
                        });
                    });                    
                });

            } catch (Exception $exc) {
                $this->logger->error($exc->getMessage());

                if($pipeline != null){
                    $pipeline->release();
                }

                unset($this->pipelines[$caller->getSession()->getId()]);
                unset($this->pipelines[$callee->getSession()->getId()]);

                $this->sendResponse($caller->getSession(), 'callResponse', "rejected");
                $this->sendResponse($callee->getSession(), 'stopCommunication', "");
            }

        } else {
            return $this->sendResponse($caller['session'], 'callResponse', "rejected");
        }        
    }    
    
    public function stop(WebSocketTransportInterface $session){
        $sessionId = $session->getId();
        if (isset($this->pipelines[$sessionId])) {
            $pipeline = $this->pipelines[$sessionId];
            $pipeline->release();
            unset($this->pipelines[$sessionId]);

            // Both users can stop the communication. A 'stopCommunication'
            // message will be sent to the other peer.
            /* @var $stopperUser UserSession */
            $stopperUser = $this->registry[$sessionId];
            $stopped = $stopperUser->getCallingFrom()? $stopperUser->getCallingFrom() : $stopperUser->getCallingTo();
            /* @var $stoppedUser UserSession */
            $stoppedUser = $this->registryGetByName($stopped);
            if($stoppedUser){
                return $this->sendResponse($stoppedUser->getSession(), 'stopCommunication', "");
            }            
        }        
    }
    
    public function play(WebSocketTransportInterface $session, array $message){
        $user = $message['user'];
        $this->logger->notice("Playing recorded call of user $user");

        $pipeline = new PlayMediaPipeline($this->client, $user, $session, function($pipeline) use ($message, $session){
            $pipeline->generateSdpAnswer($message['sdpOffer'], function($success, $data) use ($session, $pipeline){
                $sdpAnswer = $data['value'];
                $this->sendResponse($session, 'playResponse', "accepted", '', array('sdpAnswer' => $sdpAnswer));
                $pipeline->play(function($success, $data) {});                
            });
        });
    }     
}

class CallMediaPipeline {
    const RECORDING_PATH = "file:///tmp/";
    const RECORDING_EXT = ".webm";
    
    /**
     *
     * @var \MgKurentoClient\Interfaces\MediaPipeline 
     */
    protected $pipeline = null;
    /**
     *
     * @var \MgKurentoClient\Interfaces\WebRtcEndpoint
     */
    protected $callerWebRtcEP = null;
    /**
     *
     * @var \MgKurentoClient\Interfaces\WebRtcEndpoint 
     */
    protected $calleeWebRtcEP = null;
    
    /**
     *
     * @var \MgKurentoClient\Interfaces\RecorderEndpoint 
     */
    protected $recorderCaller;
    
    /**
     *
     * @var \MgKurentoClient\Interfaces\RecorderEndpoint 
     */    
    protected $recorderCallee;
    
    protected $from = '';
    protected $to;
    
    
    
    public function __construct(\MgKurentoClient\KurentoClient $client, $from, $to, callable $callback) {
        $this->from = $from;
        $this->to = $to;
        try {
            $this->pipeline = $client->createMediaPipeline(function($pipeline, $success, $data) use ($callback){
                $this->callerWebRtcEP = new \MgKurentoClient\WebRtcEndpoint($pipeline);
                $this->callerWebRtcEP->build(function($webRtcEndpoint, $success, $data) use ($callback){

                    $this->calleeWebRtcEP = new \MgKurentoClient\WebRtcEndpoint($this->pipeline);
                    $this->calleeWebRtcEP->build(function($webRtcEndpoint, $success, $data) use ($callback){                        
                        //recorders
                        $this->recorderCallee = new \MgKurentoClient\RecorderEndpoint($this->pipeline);
                        $this->recorderCallee->build(function($endpoint, $success, $data) use ($callback){                            
                            
                            $this->recorderCaller = new \MgKurentoClient\RecorderEndpoint($this->pipeline);
                            $this->recorderCaller->build(function($endpoint, $success, $data) use ($callback){
                                
                                //connections
                                $this->callerWebRtcEP->connect($this->calleeWebRtcEP, function($success, $data) use ($callback){
                                    $this->calleeWebRtcEP->connect($this->callerWebRtcEP, function($success, $data) use ($callback){
                                        //connect recorders
                                        $this->callerWebRtcEP->connect($this->recorderCaller, function($success, $data) use ($callback){
                                            $this->calleeWebRtcEP->connect($this->recorderCallee, function($success, $data) use ($callback){
                                                //external callback
                                                $callback($this);                                                
                                            });
                                        });                                                                                
                                    });                            
                                });                                 
                                

                            }, array('uri' => self::RECORDING_PATH . $this->from . self::RECORDING_EXT));                            
                            
                        }, array('uri' => self::RECORDING_PATH . $this->to . self::RECORDING_EXT));                                               
                    });                                   
                });
            });                       
        } catch (Exception $exc) {
            echo $exc->getMessage();
            if($this->pipeline != null){
                $this->pipeline->release(function(){});
            }
        }
    }

    public function generateSdpAnswerForCaller($sdpOffer, callable $callback) {
        return $this->callerWebRtcEP->processOffer($sdpOffer, $callback);
    }

    public function generateSdpAnswerForCallee($sdpOffer, callable $callback) {
        return $this->calleeWebRtcEP->processOffer($sdpOffer, $callback);
    }

    public function release() {
        if ($this->pipeline != null) {
            $this->pipeline->release(function(){});
        }
    }
    
    public function record(callable $callback) {
        $this->recorderCallee->record(function($success, $data) use ($callback){
            $this->recorderCaller->record($callback);
        });
    }    

}

class PlayMediaPipeline {
    /**
     *
     * @var \MgKurentoClient\Interfaces\MediaPipeline 
     */
    protected $pipeline = null;
    /**
     *
     * @var \MgKurentoClient\Interfaces\WebRtcEndpoint
     */
    protected $webRtc = null;
    /**
     *
     * @var \MgKurentoClient\Interfaces\PlayerEndpoint
     */
    protected $player = null;
    
    /**
     *
     * @var WebSocketTransportInterface 
     */
    protected $session = null;
    
    protected $user = '';
    
    
    
    public function __construct(\MgKurentoClient\KurentoClient $client, $user, WebSocketTransportInterface $session, callable $callback) {
        try {
            $this->user = $user;
            $this->session = $session;
            $this->pipeline = $client->createMediaPipeline(function($pipeline, $success, $data) use ($callback){
                $this->webRtc = new \MgKurentoClient\WebRtcEndpoint($pipeline);
                $this->webRtc->build(function($endpoint, $success, $data) use ($callback){
                    
                    $this->player = new \MgKurentoClient\PlayerEndpoint($this->pipeline);
                    $this->player->build(function($endpoint, $success, $data) use ($callback){
                        $this->player->connect($this->webRtc, function($success, $data) use ($callback){
                            $this->player->addEndOfStreamListener(function(){
                                echo "received end of stream event\n";
                                $this->sendPlayEnd($this->session);
                            }, function() use ($callback){
                                $callback($this);
                            });                            
                        });                        
                    }, array('uri' => CallMediaPipeline::RECORDING_PATH . $this->user . CallMediaPipeline::RECORDING_EXT));                                   
                });
            });                       
        } catch (Exception $exc) {
            echo $exc->getMessage();
            if($this->pipeline != null){
                $this->pipeline->release(function(){});
            }
        }
    }
    
    public function play(callable $callback) {
        $this->player->play($callback);
    }    

    public function generateSdpAnswer($sdpOffer, callable $callback) {
        return $this->webRtc->processOffer($sdpOffer, $callback);
    }

    public function release() {
        if ($this->pipeline != null) {
            $this->pipeline->release(function(){});
        }
    }
    
    protected function sendPlayEnd(WebSocketTransportInterface $session){
        $resultParams = array(
            "id"        => 'playEnd'
        );
        $session->sendString(json_encode($resultParams, JSON_UNESCAPED_SLASHES));        
    }    

}

class UserSession {
        
    /**
     *
     * @var WebSocketTransportInterface
     */
    protected $session = null;
    
    /**
     *
     * @var string
     */
    protected $name = '';
    
    /**
     *
     * @var string 
     */
    protected $sdpOffer;
    
    /**
     *
     * @var string 
     */
    protected $callingTo;
    
    /**
     *
     * @var string 
     */    
    protected $callingFrom;
    
    function __construct($session, $name) {
        $this->session = $session;
        $this->name = $name;
    }

    
    public function getSession() {
        return $this->session;
    }

    public function setSession($session) {
        $this->session = $session;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSdpOffer() {
        return $this->sdpOffer;
    }

    public function setSdpOffer($sdpOffer) {
        $this->sdpOffer = $sdpOffer;
    }

    public function getCallingTo() {
        return $this->callingTo;
    }

    public function setCallingTo($callingTo) {
        $this->callingTo = $callingTo;
    }

    public function getCallingFrom() {
        return $this->callingFrom;
    }

    public function setCallingFrom($callingFrom) {
        $this->callingFrom = $callingFrom;
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
