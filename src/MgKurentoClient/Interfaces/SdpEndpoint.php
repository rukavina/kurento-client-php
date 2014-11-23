<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\Interfaces;

interface SdpEndpoint extends SessionEndpoint {
    public function generateOffer();
    public function getLocalSessionDescriptor();    
    public function getRemoteSessionDescriptor();
    public function processAnswer($answer, $callback);
    public function processOffer($offer, $callback);
    
    
}
