<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;

class VoiceGenerator {
    private ElevenLabsApi $elevenLabsApi;

    /**
     * VoiceGenerator constructor.
     *
     * @param ElevenLabsApi $elevenLabsApi
     */
    public function __construct(ElevenLabsApi $elevenLabsApi) {
        $this->elevenLabsApi = $elevenLabsApi;
    }

    /**
     * Generate voice audio for the given message and voice ID.
     *
     * @param string $voiceId
     * @param string $message
     * @return string The local file path of the downloaded audio file
     * @throws Exception
     */
    public function generate_and_download(string $voiceId, string $message): string {
        $data = ['text' => $message];
        $response = $this->elevenLabsApi->textToSpeechWithVoiceId($voiceId, $data);

        if ($response->getStatusCode() === 200) {
            $result = json_decode((string)$response->getBody(), true);
            $audioUrl = $result['audio_url'];
            return $this->downloadAudio($audioUrl);
        } else {
            throw new Exception('Error generating audio: ' . $response->getReasonPhrase());
        }
    }

    /**
     * Download audio file from the given URL and save it to the voices subfolder.
     *
     * @param string $audioUrl
     * @return string The local file path of the downloaded audio file
     */
    private function downloadAudio(string $audioUrl): string {
        $voicesDirectory = 'voices';
        if (!file_exists($voicesDirectory) && !mkdir($voicesDirectory) && !is_dir($voicesDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $voicesDirectory));
        }

        $localFilePath = $voicesDirectory . '/' . uniqid() . '.mp3';

        $client = new GuzzleHttp\Client();
        $response = $client->get($audioUrl, ['sink' => $localFilePath]);

        if ($response->getStatusCode() === 200) {
            return $localFilePath;
        } else {
            throw new Exception('Error downloading audio: ' . $response->getReasonPhrase());
        }
    }
}
