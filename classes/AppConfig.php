<?php

declare(strict_types=1);
use Psr\Log\LoggerInterface;

class AppConfig
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        try {
            $configFile = new SplFileObject(__DIR__ . '/../config/config.json', 'r');
            $configContents = $configFile->fread($configFile->getSize());
            $this->config = json_decode($configContents, true);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to load the configuration file.', [
                'exception' => $exception
            ]);
            throw $exception;
        }
    }

    public function getApiKey(string $className): string
    {
        if (!isset($this->config[$className])) {
            $errorMessage = "API key not found for class {$className}.";
            $this->logger->error($errorMessage);
            throw new \InvalidArgumentException($errorMessage);
        }

        return $this->config[$className];
    }
}