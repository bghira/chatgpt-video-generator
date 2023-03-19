<?php

// Enable error reporting
error_reporting(E_ALL);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

// Initialize the logger
$log = new Logger('websocket-server');
$log->pushHandler(new StreamHandler('logs/websocket-server.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Load AppConfig
$appConfig = new AppConfig($log);

// Check if WebSocketServer configuration tree already exists
if (!$appConfig->getConfigTree('WebSocketServer')) {
    // Set the configuration tree for WebSocketServer class
    $webSocketConfig = new WebSocketServerConfig([
        'listen_addr' => '127.0.0.1',
        'listen_port' => 9090,
        'tls' => [
            'enabled' => false,
            'local_cert' => '',
            'local_pk' => '',
        ],
    ]);
    $appConfig->setConfigTree('WebSocketServer', $webSocketConfig->toArray());
} else {
    $webSocketConfig = new WebSocketServerConfig($appConfig->getConfigTree('WebSocketServer'));
}

// Generate self-signed SSL certificate if one does not exist
if ($webSocketConfig->isTlsEnabled() && !$webSocketConfig->hasCertificateFiles()) {
    $webSocketConfig->generateSelfSignedCertificate();
    $appConfig->setConfigTree('WebSocketServer', $webSocketConfig->toArray());
}

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MyWebSocketServer()
        )
    ),
    $webSocketConfig->getListenPort(),
    $webSocketConfig->getListenAddress()
);

// Start server
$server->run();