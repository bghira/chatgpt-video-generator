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
			echo(implode(PHP_EOL . PHP_EOL, $output));
			$this->log->info('Skipped node types', $skipped_node_types);
		} catch (Throwable $ex) {
			$this->log->error('Could not decode JSON, threw Exception');
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
    $extractedText = array();

    function traverseNodes($node, &$extractedText) {
        if ($node->nodeType == 3) { // Check if the node is a text node
            array_push($extractedText, trim($node->nodeValue));
        }

        if (isset($node->childNodes) && count($node->childNodes) > 0) {
            foreach ($node->childNodes as $childNode) {
                traverseNodes($childNode, $extractedText);
            }
        }
    }

    traverseNodes($jsonData->input->data, $extractedText);

    return $extractedText;
}