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
		if (empty($api_key)) {
			throw new RuntimeException('No valid API key was provided.');
		}
		$this->api_key = $api_key;
		$this->client = $client ?: new Client([
			'base_uri' => 'https://api.openai.com/v1/',
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key
			]
		]);
		$this->log = $log ?: new NullLogger();
	}

	/**
	 * Generate an image using DALL-E API and save it locally.
	 *
	 * @param string $img_path
	 * @param string $prompt
	 * @param string $localDirectory
	 * @param string $size
	 * @param int $n
	 * @return array|null
	 */
	public function generateImage(string $img_path, string $prompt, string $localDirectory, string $size = '1024x1024', int $n = 1): ?array {
		$this->log->info('Generating image', ['prompt' => $prompt, 'size' => $size, 'n' => $n]);

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
					$localFilePath = $this->saveImage($imageUrl, $localDirectory, $img_path);
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
	 * Save an image from a URL to a local file path.
	 *
	 * @param string $imageUrl
	 * @param string $localDirectory
	 * @param string $img_path
	 * @return string
	 */
	private function saveImage(string $imageUrl, string $localDirectory, string $img_path): string {
		$this->log->info('Saving image from URL', ['url' => $imageUrl, 'path' => $img_path]);

		$client = new Client();
		$response = $client->get($imageUrl, ['sink' => $img_path]);
		if ($response->getStatusCode() == 200) {
			// Move the file into a locally known filename so we can always associate it with its prompt.
			$this->log->info('Image saved successfully', ['path' => $img_path]);

			return $img_path;
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
		$this->log->info('Generating script', ['role' => $role, 'prompt' => $prompt, 'maxTokens' => $maxTokens, 'temperature' => $temperature]);
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
				$this->log->info('Generated script', ['response' => $assistantResponse]);

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
		$this->log->info('Generating image variations', ['index' => $index, 'prompt' => $prompt, 'imagePath' => $imagePath, 'localDirectory' => $localDirectory, 'n' => $n, 'size' => $size]);
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

				$this->log->info('Generated image variations saved', ['savedImages' => $savedImages]);

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
	 * @return bool True if the content is safe, False if it is unsafe.
	 */
	public function moderateContent(string $input): bool {
		$this->log->info('Moderating content', ['input' => $input]);
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
			$this->log->info('Moderated content score: ' . $total_score);
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
