<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ResponseInterface;

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
	 * Generate an image using DALL-E API and save it locally.
	 *
	 * @param integer $index
	 * @param string $prompt
	 * @param string $localDirectory
	 * @param string $size
	 * @param int $n
	 * @return array|null
	 */
	public function generateImage(int $index, string $prompt, string $localDirectory, string $size = '1024x1024', int $n = 4): ?array {
		$promptHash = md5($index . $prompt);
		$data = [
			'prompt' => $prompt,
			'n' => $n,
			'size' => $size
		];

		try {
			$response = $this->client->post('images/generations', ['json' => $data]);
			$json = json_decode((string) $response->getBody(), true);

			if (json_last_error() === JSON_ERROR_NONE) {
				$savedImages = [];
				foreach ($json['data'] as $imageData) {
					$imageUrl = $imageData['url'];
					$localFilePath = $this->saveImage($imageUrl, $localDirectory, $promptHash);
					$savedImages[] = $localFilePath;
				}

				return $savedImages;
			} else {
				$this->log->error('Failed to decode JSON response', ['json_error' => json_last_error_msg()]);

				return null;
			}
		} catch (RequestException $e) {
			$this->log->error('RequestException encountered');
			var_export((string) $e);

			return null;
		}
	}

	/**
	 * Save an image from a URL to a local file path.
	 *
	 * @param string $imageUrl
	 * @param string $localDirectory
	 * @param string $promptHash
	 * @return string
	 */
	private function saveImage(string $imageUrl, string $localDirectory, string $promptHash): string {
		$localFilePath = $localDirectory . '/' . $promptHash;

		$client = new Client();
		$response = $client->get($imageUrl, ['sink' => $localFilePath]);
		if ($response->getStatusCode() == 200) {
			// Move the file into a locally known filename so we can always associate it with its prompt.
			$this->log->info('Image saved successfully', ['path' => $localFilePath]);

			return $localFilePath;
		} else {
			$this->log->error('Failed to save the image', ['status_code' => $response->getStatusCode()]);

			throw new RuntimeException('Failed to save the image: ' . $response->getStatusCode());
		}
	}

	/**
	 * Generate a script using the OpenAI GPT-3.5 Turbo model.
	 *
	 * @param string $role
	 * @param string $prompt
	 * @param int $maxTokens
	 * @param float $temperature
	 * @return string|null
	 */
	public function generateScript(string $role, string $prompt, int $maxTokens = 3600, float $temperature = 1.0): ?string {
		$data = [
			'model' => 'gpt-3.5-turbo',
			'messages' => [
				['role' => 'system', 'content' => $role],
				['role' => 'user', 'content' => $prompt],
			],
			'max_tokens' => $maxTokens,
			'temperature' => $temperature
		];

		try {
			$response = $this->client->post('chat/completions', ['json' => $data]);
			$json = json_decode((string) $response->getBody(), true);

			if (json_last_error() === JSON_ERROR_NONE) {
				$assistantResponse = $json['choices'][0]['message']['content'];

				return $assistantResponse;
			} else {
				$this->log->error('Failed to decode JSON response', ['json_error' => json_last_error_msg()]);

				return null;
			}
		} catch (RequestException $e) {
			$this->log->error('RequestException encountered', ['message' => $e->getMessage()]);

			return null;
		}
	}
	/**
	 * Generate image variations using DALL-E API and save them locally.
	 *
	 * @param string $imagePath
	 * @param string $localDirectory
	 * @param int $n
	 * @param string $size
	 * @return array|null
	 */
	public function generateImageVariations(int $index, string $prompt, string $imagePath, string $localDirectory, int $n = 4, string $size = '1024x1024'): ?array {
		$promptHash = md5($index . $prompt);
		$data = [
			'n' => $n,
			'size' => $size,
			'image' => curl_file_create($imagePath)
		];

		try {
			$response = $this->client->post('images/variations', ['multipart' => $data]);
			$json = json_decode((string) $response->getBody(), true);

			if (json_last_error() === JSON_ERROR_NONE) {
				$savedImages = [];
				foreach ($json['data'] as $imageData) {
					$imageUrl = $imageData['url'];
					$localFilePath = $this->saveImage($imageUrl, $localDirectory, $promptHash);
					$savedImages[] = $localFilePath;
				}

				return $savedImages;
			} else {
				$this->log->error('Failed to decode JSON response', ['json_error' => json_last_error_msg()]);

				return null;
			}
		} catch (RequestException $e) {
			$this->log->error('RequestException encountered', ['message' => $e->getMessage()]);

			return null;
		}
	}

	/**
	 * Check content using the OpenAI Moderation API.
	 *
	 * @param string $input The input content to be checked for moderation.
	 * @return ResponseInterface The response from the OpenAI Moderation API.
	 *
	 * Example return value (200 OK):
	 *
	 * {
	 *   "id": "modr-6vBp3lwNUpON6Xb9W7pNs0hcO10Nz",
	 *   "model": "text-moderation-004",
	 *   "results": [
	 * 	{
	 * 	  "flagged": false,
	 * 	  "categories": {
	 * 		"sexual": false,
	 * 		"hate": false,
	 * 		"violence": false,
	 * 		"self-harm": false,
	 * 		"sexual/minors": false,
	 * 		"hate/threatening": false,
	 * 		"violence/graphic": false
	 * 	  },
	 * 	  "category_scores": {
	 * 		"sexual": 0.00004237819302943535,
	 * 		"hate": 0.000003655253294709837,
	 * 		"violence": 0.00017882340762298554,
	 * 		"self-harm": 5.321700058402712E-8,
	 * 		"sexual/minors": 2.195775579139081E-7,
	 * 		"hate/threatening": 4.635711814415799E-9,
	 * 		"violence/graphic": 3.850674374916707E-7
	 * 	  }
	 * 	}
	 *   ]
	 * }
	 */
	public function moderateContent(string $input): bool {
		$data = [
			'input' => $input
		];

		try {
			$response = $this->client->post('moderations', ['json' => $data]);
			$body = (string) $response->getBody();
			$result = json_decode($body, true);
			$result = $result['results'][0];
			if (!isset($result['category_scores']) || !is_array($result['category_scores'])) {
				print_r($result);
				$this->log->error('We did not receive a moderation result. This is probably a bad sign. Exiting.', $result);

				throw new RuntimeException();
			}
			$this->log->info('Moderated content', $result['category_scores']);
			if ($result['flagged'] === true) {
				return false;
			}
			$values = array_values($result['category_scores']);
			$this->log->info('All scores', $values);
			$total_score = array_sum($values);
			$this->log->info('Moderated content score: '.$total_score);
			if ($total_score > 3) {
				// Entering dangerous territory.
				return false;
			}
			return true;
		} catch (RequestException $e) {
			$this->log->error('RequestException encountered', ['message' => $e->getMessage()]);

			throw $e;
		}
	}
}
