<?php

use Ratchet\ComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface as MessageComponentInterface;

class MyWebSocketServer implements Ratchet\MessageComponentInterface {
	public function onOpen(Ratchet\ConnectionInterface $connection) {
		echo "New client connected: {$connection->resourceId}\n";
	}
	public function onMessage(Ratchet\ConnectionInterface $from, $message) {
		// Send message back to client
		$from->send("You said: {$message}\n");
		// Send message to CLI
		fwrite(STDOUT, "Client {$from->resourceId}: {$message}\n");
	}
	public function onClose(Ratchet\ConnectionInterface $connection) {
		echo "Client {$connection->resourceId} disconnected\n";
	}
    public function onError(Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }

}
