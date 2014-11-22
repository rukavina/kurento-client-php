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

interface MediaObject {
    public function create($params, $callback);
    public function getId();    
    public function getMediaPipeline();
    public function getParent();
    public function release();
    
}
