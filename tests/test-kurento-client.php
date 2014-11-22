<?php

require_once("../vendor/autoload.php");                // Composer autoloader

$loop = \React\EventLoop\Factory::create();


$client = \MgKurentoClient\KurentoClient::create('ws://127.0.0.1:8888/kurento', $loop, function($client){
    $client->createMediaPipeline(function($pipeline, $success, $data){
        $mediaBuilder = new \MgKurentoClient\MediaElementBuilder($pipeline);
        $mediaBuilder->build(function($webRtcEndpoint, $success, $data){
            $webRtcEndpoint->connect($webRtcEndpoint, function($success, $data){
                print('connected');
            });
        }, 'WebRtcEndpoint');
    });    
});


$loop->run();


