<?php
/**
 * A simple PHP Web Socket Server.
 */
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketHub implements MessageComponentInterface {
	protected $clients;
	protected $addresses = [];
	protected $log;
	public function __construct($log) {
		$this->clients = new \SplObjectStorage;
		$this->log = $log;
	}

	public function onOpen(ConnectionInterface $conn) {
		if (isset($conn->remoteAddress) && in_array($conn->remoteAddress, $this->addresses)) {
			$this->log->info('Rejected connection for already-connected address: ' . $conn->remoteAddress);

			return;
		}
		if (isset($conn->remoteAddress)) {
			$this->addresses[$conn->resourceId] = $conn->remoteAddress;
		} else {
			$this->addresses[$conn->resourceId] = null;
		}
		$this->clients->attach($conn);
		$this->log->info("New connection! ({$conn->resourceId}) Remote IP: {$conn->remoteAddress}");
	}

	public function onMessage(ConnectionInterface $from, $msg) {
		// Handle incoming messages from WebSocket clients
		try {
			$this->log->info('Received message: ' . $msg);
			$data = json_decode($msg);
			// $input = $data['input']['data'];
			// $nodes = $input['childNodes'];
			// $skipped_node_types = [];
			$output = extractTextContent($data);
			// $output = [];
			// foreach($nodes as $node) {
			// 	foreach($node['childNodes'] as $id => $child) {
			// 		// Looking for text output
			// 		if ($child['nodeType'] === 3 && !empty($child['nodeValue'])) {
			// 			$output[] = $child['nodeValue'];
			// 		} else {
			// 			// elseif (isset($child['nodeValue']) && !empty($child['nodeValue'])) {
			// 			$skipped_node_types[$child['nodeType']] = $child;
			// 		}
			// 	}
			// }
			$this->log->info('ChatGPT returned message');
			echo($output . PHP_EOL);
		} catch (Throwable $ex) {
			$this->log->error('Could not decode JSON, threw Exception: ' . $ex->getFile() . ':' . $ex->getLine() . ' ' . $ex->getMessage());
		}
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'message-data-' . $from->resourceId, $msg);
	}

	public function onClose(ConnectionInterface $conn) {
		unset($this->addresses[$conn->resourceId]);
		$this->clients->detach($conn);
		$this->log->info("Connection {$conn->resourceId} has disconnected - " . count($this->addresses) . ' active connections remain.');
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->log->error("An error has occurred: {$e->getMessage()}");
		$conn->close();
	}

	public function broadcast($message) {
		$this->log->info('Reached broadcast..');
		foreach ($this->clients as $client) {
			if (isset($client->remoteAddress)) {
				$this->log->info('Would send "' . $message . '" to ' . $client->remoteAddress);
			}
			$client->send($message);
		}
	}
}

function extractTextContent($jsonData) {
	global $log;
	$extractor = new DOMExtractor($jsonData, $log);

	return $extractor->extract();
}