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
	 * Generate an image using DALL-E API and save it locally.
	 *
	 * @param string $prompt
	 * @param string $localDirectory
	 * @param string $size
	 * @param int $n
	 * @return array|null
	 */
	public function generateImage(string $prompt, string $localDirectory, string $size = '1024x1024', int $n = 4): ?array {
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
					$localFilePath = $this->saveImage($imageUrl, $localDirectory);
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
	 * @return string
	 */
	private function saveImage(string $imageUrl, string $localDirectory): string {
		$imageFileName = basename(parse_url($imageUrl, PHP_URL_PATH));
		$localFilePath = $localDirectory . '/' . $imageFileName;

		$client = new Client();
		$response = $client->get($imageUrl, ['sink' => $localFilePath]);

		if ($response->getStatusCode() == 200) {
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
    public function generateImageVariations(string $imagePath, string $localDirectory, int $n = 4, string $size = '1024x1024'): ?array {
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
                    $localFilePath = $this->saveImage($imageUrl, $localDirectory);
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
}
