<?php


namespace MgKurentoClient;


/**
 * Factory to create {MediaPipeline} in the media server.
 *
 * @author Milan Rukavina
 */
class KurentoClient {

	public static function create($websocketUrl) {
		return new KurentoClient(new JsonRpcClientWebSocket(websocketUrl));
	}

	/**
	 * Creates a new {MediaPipeline} in the media server
	 *
	 * @return The media pipeline
	 */
	public function createMediaPipeline() {
	}

}