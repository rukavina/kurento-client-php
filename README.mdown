Kurento Client PHP
==================

Kurento Client PHP library for [Kurento WebRTC media server](http://www.kurento.org/) which implements client side of [Kurento Protocol](http://www.kurento.org/docs/current/mastering/kurento_protocol.html)


Installation
------------
The easiest way to install this library is using *composer*. Update your `composer.json`

```javascript
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/rukavina/kurento-client-php"
        }
    ],
    "require": {
        "rukavinamilan/kurento-client-php": "dev-master"
    }
```

and run

```
composer install
```

For actual Kurento WebRTC media server installation please check http://www.kurento.org/docs/current/installation_guide.html


Usage
-----

This is the *hello world* example. Read more at official [tutorials' page](http://www.kurento.org/docs/current/tutorials.html).

```php
<?php
//composer autoload included
require_once("vendor/autoload.php");

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
```

Generic elements
----------------

If some remove object are not directly implemented as php classes you can still create and use them via generic `MediaObject` class.
It provides generic method:

```php
    public function remoteCreate($remoteType, callable $callback, array $params = array());    
    public function remoteInvoke($operation, $operationParams, callable $callback);    
    public function remoteRelease(callable $callback);    
    protected function remoteSubscribe($type, $onEvent, callable $callback);    
    public function remoteUnsubscribe($subscription, callable $callback);
```

The same hello world example could be implemented using generic class/methods as

```php
$this->client = \MgKurentoClient\KurentoClient::create($this->wsUrl, $this->loop, $this->logger, function($client){
    $this->client->createMediaPipeline(function($pipeline, $success, $data){
        $webRtcEndpoint = new \MgKurentoClient\MediaObject($pipeline);
        $webRtcEndpoint->remoteCreate('WebRtcEndpoint', function($webRtcEndpoint, $success, $data){
            $webRtcEndpoint->connect($webRtcEndpoint, function($success, $data) use ($webRtcEndpoint){
                $webRtcEndpoint->remoteInvoke('processOffer', array('offer' => $this->offer), function($success, $data){
                    echo $data['value'];
                    //we don't need the loop anymore , we're exiting now
                    $this->loop->stop();
                });
            });
        });
    });    
});
$this->loop->run();
```

Examples installation
---------------------

Don't forget to install Kurento server first http://www.kurento.org/docs/current/installation_guide.html

then 

```
git clone https://github.com/rukavina/kurento-client-php.git
composer install
```

Then check `README` file in each particular example folder.

Read more at official [tutorials' page](http://www.kurento.org/docs/current/tutorials.html).


Requirements
------------

- Kurento Client PHP works with PHP 5.3 or above.
- [PHP Websocket library](https://github.com/Devristo/phpws)


Author
------

Milan Rukavina


License
-------

Kurento Client PHP is licensed under the MIT License - see the `LICENSE` file for details

Acknowledgements
----------------

This library is heavily inspired by official [java](https://github.com/Kurento/kurento-java) and [javascript](https://github.com/Kurento/kurento-client-js) clients by Kurento