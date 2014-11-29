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

class RecorderEndpoint extends MediaElement implements Interfaces\RecorderEndpoint {
    public function record(callable $callback){
        $this->remoteInvoke('record', array(), $callback);
    } 
}
