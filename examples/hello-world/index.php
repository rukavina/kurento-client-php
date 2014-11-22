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



class DemoApp{
    protected $offer = null;
    protected $loop;
    protected $logger;
    protected $wsUrl;
    protected $client;


    function __construct($offer, $wsUrl) {
        $this->offer = $offer;
        $this->wsUrl = $wsUrl;
        $this->loop = \React\EventLoop\Factory::create();

        $this->logger = new \Zend\Log\Logger();                
        $writer = new \Zend\Log\Writer\Stream("app.log");        
        $this->logger->addWriter($writer);
    }
    
    public function run(){
        $this->client = \MgKurentoClient\KurentoClient::create($this->wsUrl, $this->loop, $this->logger, function($client){
            $this->client->createMediaPipeline(function($pipeline, $success, $data){
                $mediaBuilder = new \MgKurentoClient\MediaElementBuilder($pipeline);
                $mediaBuilder->build(function($webRtcEndpoint, $success, $data){
                    $webRtcEndpoint->connect($webRtcEndpoint, function($success, $data) use ($webRtcEndpoint){
                        /* @var $webRtcEndpoint \MgKurentoClient\WebRtcEndpoint */
                        $webRtcEndpoint->processOffer($this->offer, function($success, $data){
                            echo $data['value'];
                            $this->loop->stop();
                        });
                    });
                }, 'WebRtcEndpoint');
            });    
        });
        $this->loop->run();
    }

}

$offer = file_get_contents('php://input');
$demoApp = new DemoApp($offer, 'ws://127.0.0.1:8888/kurento');
$demoApp->run();


