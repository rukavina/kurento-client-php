<?php

require_once("../vendor/autoload.php");

echo "\nwebrtc client test started\n";

$webRtc = \MgKurentoClient\WebRtc\Client::getInstance();

$webRtcClient = $webRtc->initClient('ws://localhost:8080');

$webRtcClient->on("message", function($message){
    echo $message . "\n";
});
$webRtcClient->send('Hello');