<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ElevenLabsApi
{
    private const BASE_URL = 'https://api.elevenlabs.io';

    private string $apiKey;
    private Client $client;

    /**
     * ElevenLabsApi constructor.
     *
     * @param string $apiKey
     * @param Client|null $client
     */
    public function __construct(string $apiKey, Client $client = null)
    {
        $this->apiKey = $apiKey;
        $this->client = $client ?? new Client();
    }

    /**
     * @return Response
     */
    public function getHistory(): Response
    {
        $url = self::BASE_URL . '/v1/history';
        return $this->makeRequest('GET', $url);
    }

    /**
     * @param string $historyItemId
     * @return Response
     */
    public function getAudioFromHistoryItem(string $historyItemId): Response
    {
        $url = self::BASE_URL . "/v1/history/{$historyItemId}";
        return $this->makeRequest('GET', $url);
    }

    /**
     * @param array $historyItemIds
     * @return Response
     */
    public function deleteHistoryItems(array $historyItemIds): Response
    {
        $url = self::BASE_URL . '/v1/history/delete';
        $body = json_encode(['history_item_ids' => $historyItemIds]);
        return $this->makeRequest('POST', $url, $body);
    }

    /**
     * @param string $historyItemId
     * @return Response
     */
    public function deleteHistoryItem(string $historyItemId): Response
    {
        $url = self::BASE_URL . "/v1/history/{$historyItemId}";
        return $this->makeRequest('DELETE', $url);
    }

    /**
     * @param array $historyItemIds
     * @return Response
     */
    public function downloadHistoryItems(array $historyItemIds): Response
    {
        $url = self::BASE_URL . '/v1/history/download';
        $body = json_encode(['history_item_ids' => $historyItemIds]);
        return $this->makeRequest('POST', $url, $body);
    }

    /**
     * @param string $voiceId
     * @param array $data
     * @return Response
     */
    public function textToSpeech(string $voiceId, array $data): Response
    {
        $url = self::BASE_URL . "/v1/text-to-speech/{$voiceId}/stream";
        $body = json_encode($data);
        return $this->makeRequest('POST', $url, $body);
    }
    /**
     * @param string $voiceId
     * @param array $data
     * @return Response
     */
    public function textToSpeechWithVoiceId(string $voiceId, array $data): Response
    {
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
    private function makeRequest(string $method, string $url, string $body = null): Response
    {
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
}