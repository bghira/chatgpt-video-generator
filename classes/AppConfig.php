<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;

/**
 * The AppConfig class represents an application configuration loader.
 */
class AppConfig {
	/**
	 * The logger used for logging errors.
	 *
	 * @var LoggerInterface
	 */
	private LoggerInterface $logger;

	/**
	 * The configuration array.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Creates a new AppConfig instance.
	 *
	 * @param LoggerInterface $logger The logger to use for logging errors.
	 */
	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
		$this->loadConfig();
	}

	/**
	 * Loads the configuration file and sets the config array.
	 *
	 * @throws Exception If the configuration file cannot be loaded.
	 */
	private function loadConfig(): void {
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

	/**
	 * Retrieves the API key for the specified class name.
	 *
	 * @param string $className The name of the class for which to retrieve the API key.
	 *
	 * @throws InvalidArgumentException If the API key cannot be found.
	 * @return string The API key for the specified class name.
	 *
	 */
	public function getApiKey(string $className): string|array {
		if (!isset($this->config[$className])) {
			$errorMessage = "API key not found for class {$className}.";
			$this->logger->error($errorMessage);

			throw new \InvalidArgumentException($errorMessage);
		}

		return $this->config[$className];
	}

	/**
	 * Sets the configuration tree for the specified class name.
	 *
	 * @param string $className The name of the class for which to set the configuration tree.
	 * @param array $configTree The configuration tree to set for the specified class name.
	 *
	 * @return void
	 */
	public function setConfigTree(string $className, array $configTree): void {
		$this->config[$className] = $configTree;
		$this->saveConfig();
	}

	/**
	 * Saves the configuration to disk.
	 *
	 * @return void
	 */
	private function saveConfig(): void {
		try {
			$configFile = new SplFileObject(__DIR__ . '/../config/config.json', 'w');
			$configFile->fwrite(json_encode($this->config, JSON_PRETTY_PRINT));
		} catch (\Exception $exception) {
			$this->logger->error('Failed to save the configuration file.', [
			'exception' => $exception
		]);

			throw $exception;
		}
	}

	/**
	 * Retrieves the configuration tree for the specified class name.
	 *
	 * @param string $className The name of the class for which to retrieve the configuration tree.
	 *
	 * @return array|null The configuration tree for the specified class name, or null if not found.
	 */
	public function getConfigTree(string $className): ?array {
		return $this->config[$className] ?? null;
	}
}
