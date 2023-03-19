<?php

class CommandLineMessageHandler
{
    public $websocketServer;

    public function __construct($websocketServer)
    {
        $this->websocketServer = $websocketServer;
    }

    public function broadcast($message)
    {
        $this->websocketServer->broadcast($message);
    }
}
