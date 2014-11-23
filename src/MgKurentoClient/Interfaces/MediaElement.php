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

interface MediaElement extends MediaObject {    
    public function connect(MediaElement $sink, $callback);
    public function addSource(MediaElement $source);
    public function getMediaSinks();
    public function getMediaSrcs();
    
    
}
