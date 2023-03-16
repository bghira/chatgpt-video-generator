<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ElevenLabsApi {
	private const BASE_URL = 'https://api.elevenlabs.io';

	private string $apiKey;
	private Client $client;

	/**
	 * ElevenLabsApi constructor.
	 *
	 * @param string $apiKey
	 * @param Client|null $client
	 */
	public function __construct(string $apiKey, Client $client = null) {
		$this->apiKey = $apiKey;
		$this->client = $client ?? new Client();
	}

	/**
	 * Returns metadata about all your generated audio.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "items": [
	 *     {
	 *       "id": "VW7YKqPnjY4h39yTbx2L",
	 *       "title": "Generated Audio 1",
	 *       "duration": 120,
	 *       "created_at": "2023-03-16T08:00:00Z",
	 *       "url": "https://download-link.example.com/your_history_item_audio.mp3"
	 *     },
	 *     {
	 *       "id": "AbCDeFgH1I2jK3LmN4O5",
	 *       "title": "Generated Audio 2",
	 *       "duration": 90,
	 *       "created_at": "2023-03-15T08:00:00Z",
	 *       "url": "https://download-link.example.com/your_history_item_audio2.mp3"
	 *     }
	 *   ]
	 * }
	 */
	public function getHistory(): Response {
		$url = self::BASE_URL . '/v1/history';

		return $this->makeRequest('GET', $url);
	}

	/**
	 * Returns the audio of a history item.
	 *
	 * @param string $history_item_id The history item ID to get audio from.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "url": "https://download-link.example.com/your_history_item_audio.mp3"
	 * }
	 */
	public function getAudioFromHistoryItem(string $historyItemId): Response {
		$url = self::BASE_URL . "/v1/history/{$historyItemId}";

		return $this->makeRequest('GET', $url);
	}

	/**
	 * Delete a number of history items by their IDs.
	 *
	 * @param array $history_item_ids An array of history item IDs to delete.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "status": "success",
	 *   "message": "Selected history items deleted successfully."
	 * }
	 */
	public function deleteHistoryItems(array $historyItemIds): Response {
		$url = self::BASE_URL . '/v1/history/delete';
		$body = json_encode(['history_item_ids' => $historyItemIds]);

		return $this->makeRequest('POST', $url, $body);
	}

	/**
	 * Delete a history item by its ID
	 *
	 * @param string $history_item_id The ID of the history item to be deleted
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "message": "History item deleted successfully"
	 * }
	 */
	public function deleteHistoryItem(string $historyItemId): Response {
		$url = self::BASE_URL . "/v1/history/{$historyItemId}";

		return $this->makeRequest('DELETE', $url);
	}

	/**
	 * Download one or more history items.
	 *
	 * @param array $history_item_ids An array of history item IDs to download.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "url": "https://download-link.example.com/your_downloaded_audio.zip"
	 * }
	 */
	public function downloadHistoryItems(array $historyItemIds): Response {
		$url = self::BASE_URL . '/v1/history/download';
		$body = json_encode(['history_item_ids' => $historyItemIds]);

		return $this->makeRequest('POST', $url, $body);
	}

	/**
	 * Convert text to speech
	 *
	 * @param string $text The text to be converted into speech
	 * @param array $options Optional parameters for the TTS conversion
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "history_item_id": "VW7YKqPnjY4h39yTbx2L",
	 *   "text": "Hello, world!",
	 *   "options": {
	 *     "voice": "Joanna",
	 *     "language": "en-US",
	 *     "output_format": "mp3"
	 *   },
	 *   "audio_url": "https://api.elevenlabs.io/v1/history/VW7YKqPnjY4h39yTbx2L/audio"
	 * }
	 */
	public function textToSpeech(string $voiceId, array $data): Response {
		$url = self::BASE_URL . "/v1/text-to-speech/{$voiceId}/stream";
		$body = json_encode($data);

		return $this->makeRequest('POST', $url, $body);
	}
	/**
	 * Convert text to speech with a specific Voice ID
	 *
	 * @param string $text The text to be converted into speech
	 * @param string $voiceId The ID of the voice to be used for TTS conversion
	 * @param array $options Optional parameters for the TTS conversion
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "history_item_id": "VW7YKqPnjY4h39yTbx2L",
	 *   "text": "Hello, world!",
	 *   "options": {
	 *     "voice_id": "Joanna",
	 *     "language": "en-US",
	 *     "output_format": "mp3"
	 *   },
	 *   "audio_url": "https://api.elevenlabs.io/v1/history/VW7YKqPnjY4h39yTbx2L/audio"
	 * }
	 */
	public function textToSpeechWithVoiceId(string $voiceId, array $data): Response {
		$url = self::BASE_URL . "/v1/text-to-speech/{$voiceId}";
		$body = json_encode($data);

		return $this->makeRequest('POST', $url, $body);
	}

	/**
	 * Make a request to the ElevenLabs API.
	 *
	 * @param string $method
	 * @param string $url
	 * @param string|null $body
	 * @return Response
	 */
	private function makeRequest(string $method, string $url, string $body = null): Response {
		$options = [
			'headers' => [
				'Xi-Api-Key' => $this->apiKey,
				'Content-Type' => 'application/json',
			],
		];

		if ($body !== null) {
			$options['body'] = $body;
		}

		return $this->client->request($method, $url, $options);
	}

	/**
	 * Delete a history item by its ID.
	 *
	 * @param string $history_item_id The history item ID to delete.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "status": "success",
	 *   "message": "History item deleted successfully."
	 * }
	 */
	public function deleteSample(string $voiceId, string $sampleId): Response {
		$url = self::BASE_URL . "/v1/voices/{$voiceId}/samples/{$sampleId}";

		return $this->makeRequest('DELETE', $url);
	}

	/**
	 * Returns the audio corresponding to a sample attached to a voice.
	 *
	 * @param string $voiceId
	 * @param string $sampleId
	 * @return Response
	 * Example of a successful response (200 OK):
	 * Content-Type: audio/mpeg
	 * (Binary audio content)
	 */
	public function getAudioFromSample(string $voiceId, string $sampleId): Response {
		$url = self::BASE_URL . "/v1/voices/{$voiceId}/samples/{$sampleId}/audio";

		return $this->makeRequest('GET', $url);
	}

	/**
	 * Delete a history item by its ID.
	 *
	 * @param string $historyItemId
	 * @return Response
	 */
	public function deleteHistoryItemById(string $historyItemId): Response {
		$url = self::BASE_URL . "/v1/history/{$historyItemId}";

		return $this->makeRequest('DELETE', $url);
	}

	/**
	 * Download one or more history items.
	 *
	 * @param array $historyItemIds
	 * @return Response
	 */
	public function downloadHistoryItemsByIds(array $historyItemIds): Response {
		$url = self::BASE_URL . '/v1/history/download';
		$body = json_encode(['history_item_ids' => $historyItemIds]);

		return $this->makeRequest('POST', $url, $body);
	}

	/**
	 * Get metadata about all your generated audio.
	 *
	 * @return Response
	 *
	 * Example of a successful response (200 OK):
	 * {
	 *   "items": [
	 *     {
	 *       "id": "VW7YKqPnjY4h39yTbx2L",
	 *       "created_at": "2023-03-16T12:30:00Z",
	 *       "request": {
	 *         "text": "Hello, world!",
	 *         "voice": "en-US-Wavenet-A",
	 *         "language_code": "en-US",
	 *         "speed": 1,
	 *         "pitch": 0,
	 *         "volume_gain_db": 0
	 *       },
	 *       "duration_seconds": 2.16
	 *     },
	 *     {
	 *       "id": "yv9dA7SxQx2zG8f4Zv1m",
	 *       "created_at": "2023-03-15T14:45:00Z",
	 *       "request": {
	 *         "text": "Good morning!",
	 *         "voice": "en-US-Wavenet-B",
	 *         "language_code": "en-US",
	 *         "speed": 1,
	 *         "pitch": 0,
	 *         "volume_gain_db": 0
	 *       },
	 *       "duration_seconds": 1.8
	 *     }
	 *   ]
	 * }
	 */
	public function getGeneratedItems(): Response {
		$url = self::BASE_URL . '/v1/history';

		return $this->makeRequest('GET', $url);
	}

	/**
	 * Get the audio of a history item by its ID.
	 *
	 * @param string $historyItemId
	 * @return Response
	 */
	public function getAudioFromHistoryItemById(string $historyItemId): Response {
		$url = self::BASE_URL . "/v1/history/{$historyItemId}/audio";

		return $this->makeRequest('GET', $url);
	}

	/**
	 * Delete a number of history items by their IDs.
	 *
	 * @param array $historyItemIds
	 * @return Response
	 */
	public function deleteHistoryItemsByIds(array $historyItemIds): Response {
		$url = self::BASE_URL . '/v1/history/delete';
		$body = json_encode(['history_item_ids' => $historyItemIds]);

		return $this->makeRequest('POST', $url, $body);
	}
}
