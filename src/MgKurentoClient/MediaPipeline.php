<?php

namespace MgKurentoClient;

interface MediaPipeline extends MediaObject {
    /**
     * @return  \MgKurentoClient\JsonRpc\Client
     */
    public function getJsonRpc();
}
