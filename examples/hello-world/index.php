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
        //required react even loop
        $this->loop = \React\EventLoop\Factory::create();

        $this->logger = new \Zend\Log\Logger();                
        $writer = new \Zend\Log\Writer\Null();        
        $this->logger->addWriter($writer);
    }
    
    public function run(){
        $this->client = \MgKurentoClient\KurentoClient::create($this->wsUrl, $this->loop, $this->logger, function($client){
            $this->client->createMediaPipeline(function($pipeline, $success, $data){
                $webRtcEndpoint = new \MgKurentoClient\WebRtcEndpoint($pipeline);
                $webRtcEndpoint->build(function($webRtcEndpoint, $success, $data){
                    $webRtcEndpoint->connect($webRtcEndpoint, function($success, $data) use ($webRtcEndpoint){
                        /* @var $webRtcEndpoint \MgKurentoClient\WebRtcEndpoint */
                        $webRtcEndpoint->processOffer($this->offer, function($success, $data){
                            echo $data['value'];
                            //we don't need the loop anymore , we're exiting now
                            $this->loop->stop();
                        });
                    });
                });
            });    
        });
        $this->loop->run();
    }

}

/*
 * Starting here
 */

//get raw post body
$offer = file_get_contents('php://input');
//init the app
$demoApp = new DemoApp($offer, 'ws://127.0.0.1:8888/kurento');
//start the app
$demoApp->run();


