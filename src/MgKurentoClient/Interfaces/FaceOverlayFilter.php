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

interface FaceOverlayFilter extends Filter {
    public function setOverlayedImage($uri, $offsetXPercent, $offsetYPercent, $widthPercent, $heightPercent, callable $callback);
}
