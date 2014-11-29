<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient;

class PlayerEndpoint extends MediaElement implements Interfaces\PlayerEndpoint {
    public function play(callable $callback){
        $this->remoteInvoke('play', array(), $callback);
    }
    
    public function addEndOfStreamListener(callable $listener, callable $callback){
        $this->remoteSubscribe('EndOfStream', $listener, $callback);
    }    
}
