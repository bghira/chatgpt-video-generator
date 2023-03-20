<?php

// Enable error reporting
error_reporting(E_ALL);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
use React\EventLoop\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\Server as SocketServer;

// Initialize the logger
$log = new Logger('websocketserver');
$log->pushHandler(new StreamHandler(__DIR__ . DIRECTORY_SEPARATOR . '/logs/websocket-server.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$log->info('Log initiated.');

// Load AppConfig
$appConfig = new AppConfig($log);

// Check if WebSocketServer configuration tree already exists
if (!$appConfig->getConfigTree('WebSocketServer')) {
    // Set the configuration tree for WebSocketServer class
    $webSocketConfig = new WebSocketServerConfig([
        'listen_addr' => '::1',
        'listen_port' => 9090,
        'tls' => [
            'enabled' => false,
            'local_cert' => 'config/ssl/cert.pem',
            'local_pk' => 'config/ssl/private.key',
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

// Create an event loop
$loop = Factory::create();

// Create WebSocket server
$myWebSocketServer = new WebSocketHub($log);

// Initialize WebSocket server with IoServer
$server = new Ratchet\Server\IoServer(
    new HttpServer(
        new WsServer(
            $myWebSocketServer
        )
    ),
    new SocketServer("{$webSocketConfig->getListenAddress()}:{$webSocketConfig->getListenPort()}", $loop),
    $loop
);

// Initialize CommandLineMessageHandler
$commandLineMessageHandler = new CommandLineMessageHandler($myWebSocketServer);

// Add a periodic timer that checks for command-line input every 100ms
$loop->addPeriodicTimer(0.1, function () use ($commandLineMessageHandler, $log) {
    $read = [STDIN];
    $write = $except = null;
    $result = stream_select($read, $write, $except, 0);
    if ($result && in_array(STDIN, $read)) {
        $log->debug('Input data received.');
        $input = trim(fgets(STDIN));
        if ($input !== '') {
            $log->debug('Broadcasting message.');
            $commandLineMessageHandler->websocketServer->broadcast($input);
        }
    }
});

function errorHandler($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}

set_error_handler('errorHandler', E_WARNING);


// Run the event loop
$loop->run();

