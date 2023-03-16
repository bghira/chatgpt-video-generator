<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class OpenAI
 */
class OpenAI {
	/**
	 * @var string
	 */
	private $api_key;

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var LoggerInterface
	 */
	private $log;

	/**
	 * OpenAI constructor.
	 *
	 * @param string $api_key
	 * @param Client|null $client
	 * @param LoggerInterface|null $log
	 */
	public function __construct(string $api_key, Client $client = null, LoggerInterface $log = null) {
		$this->api_key = $api_key;
		$this->client = $client ?: new Client([
			'base_uri' => 'https://api.openai.com/v1/',
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key
			]
		]);
		$this->log = $log ?: new NullLogger();
		if (empty($api_key)) {
			throw new RuntimeException('No valid API key was provided.');
		}
	}

	/**
	 * Generate an image using DALL-E API.
	 *
	 * @param string $prompt
	 * @param string $size
	 * @param int $n
	 * @return array|null
	 */
	public function generateImage(string $prompt, string $size = '1024x1024', int $n = 1): ?array {
		$data = [
			'prompt' => $prompt,
			'n' => $n,
			'size' => $size
		];

		try {
			$response = $this->client->post('images/generations', ['json' => $data]);
			$json = json_decode($response->getBody(), true);

			if (json_last_error() === JSON_ERROR_NONE) {
				return $json;
			} else {
				$this->log->error('Failed to decode JSON response', ['json_error' => json_last_error_msg()]);

				return null;
			}
		} catch (RequestException $e) {
			$this->log->error('RequestException encountered', ['message' => $e->getMessage()]);

			return null;
		}
	}
}
