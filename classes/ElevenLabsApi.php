<?php

class ElevenLabsApi
{
    const BASE_URL = 'https://api.elevenlabs.io';

    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function makeRequest($method, $url, $params = [], $headers = [])
    {
        // Implement the logic for making requests using cURL or another HTTP client library
    }

    public function getHistory()
    {
        $url = self::BASE_URL . '/v1/history';
        $headers = ['xi-api-key' => $this->apiKey];
        return $this->makeRequest('GET', $url, [], $headers);
    }

    public function getAudioFromHistoryItem($historyItemId)
    {
        $url = self::BASE_URL . "/v1/history/{$historyItemId}/audio";
        $headers = ['xi-api-key' => $this->apiKey];
        return $this->makeRequest('GET', $url, [], $headers);
    }

    public function deleteHistoryItems(array $historyItemIds)
    {
        $url = self::BASE_URL . '/v1/history/delete';
        $headers = ['xi-api-key' => $this->apiKey];
        $body = json_encode(['history_item_ids' => $historyItemIds]);
        return $this->makeRequest('POST', $url, $body, $headers);
    }

    public function deleteHistoryItem($historyItemId)
    {
        $url = self::BASE_URL . "/v1/history/{$historyItemId}";
        $headers = ['xi-api-key' => $this->apiKey];
        return $this->makeRequest('DELETE', $url, [], $headers);
    }

    public function downloadHistoryItems(array $historyItemIds)
    {
        $url = self::BASE_URL . '/v1/history/download';
        $headers = ['xi-api-key' => $this->apiKey];
        $body = json_encode(['history_item_ids' => $historyItemIds]);
        return $this->makeRequest('POST', $url, $body, $headers);
    }

    public function textToSpeech($voiceId, array $data)
    {
        $url = self::BASE_URL . "/v1/text-to-speech/{$voiceId}/stream";
        $headers = ['xi-api-key' => $this->apiKey];
        $body = json_encode($data);
        return $this->makeRequest('POST', $url, $body, $headers);
    }

    public function textToSpeechWithVoiceId($voiceId, array $data)
    {
        $url = self::BASE_URL . "/v1/text-to-speech/{$voiceId}";
        $headers = ['xi-api-key' => $this->apiKey];
        $body = json_encode($data);
        return $this->makeRequest('POST', $url, $body, $headers);
    }

}