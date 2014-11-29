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

interface PlayerEndpoint  extends UriEndpoint {
    public function play(callable $callback);
    public function addEndOfStreamListener(callable $listener, callable $callback);
}
