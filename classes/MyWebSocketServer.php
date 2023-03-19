<?php
// MyWebSocketServer.php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class MyWebSocketServer implements MessageComponentInterface {
	protected $clients;
	protected $addresses = [];
    protected $log;
	public function __construct($log) {
		$this->clients = new \SplObjectStorage;
		$this->log = $log;
	}

    public function onOpen(ConnectionInterface $conn) {
        if (in_array($conn->remoteAddress, $this->addresses)) {
            $this->log->info('Rejected connection for already-connected address: ' . $conn->remoteAddress);
            return;
        }
        $this->addresses[] = $conn->remoteAddress;
        $this->clients->attach($conn);
        $this->log->info("New connection! ({$conn->resourceId}) Remote IP: {$conn->remoteAddress}");
    }
    

	public function onMessage(ConnectionInterface $from, $msg) {
		// Handle incoming messages from WebSocket clients
        $this->log->info(sprintf('Received message from %d: %s', $from->resourceId, $msg));
	}

	public function onClose(ConnectionInterface $conn) {
        unset($this->addresses[$conn->remoteAddress]);
		$this->clients->detach($conn);
		$this->log->info("Connection {$conn->resourceId} has disconnected - " . count($this->addresses) . " active connections remain.");
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->log->error("An error has occurred: {$e->getMessage()}");
		$conn->close();
	}

	public function broadcast($message) {
        $this->log->info('Reached broadcast..');
		foreach ($this->clients as $client) {
            $this->log->info('Would send ' . $message . ' to ' . $client->remoteAddress);
			$client->send($message);
		}
	}
}
