<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

// Initialize the logger
$log = new Logger('scene');
$log->pushHandler(new StreamHandler('logs/scene.log', Logger::DEBUG));

// Initialize AppConfig
$appConfig = new AppConfig($log);

// Retrieve API keys from AppConfig
$openaiApiKey = $appConfig->getApiKey('OpenAI');
$elevenLabsApiKey = $appConfig->getApiKey('ElevenLabsApi');

// Initialize OpenAI and ElevenLabsApi objects with the API keys and logger
$openai = new OpenAI($openaiApiKey, null, $log);
$elevenLabsApi = new ElevenLabsApi($elevenLabsApiKey, null, $log);

$log_data = [];

// Generate script
$role = "you are a scriptwriter from William S Burroughs era. respond as he would.";
$prompt = "Low-key good version of harry potter";
$script = $openai->generateScript($role, $prompt);
$log_data['script'] = $script;
// Save script to file
file_put_contents(__DIR__ . '/scripts/' . time() . '.txt', $script);

// Generate image prompt
$role = "you are a brilliant AI prompt writer. create an image prompt based on this script.";
$image_prompt = $openai->generateScript($role, $script);
$log_data['image_prompt'] = $image_prompt;

// Generate images
$images = $openai->generateImage($image_prompt, 'images/');
$log_data['images'] = $images;

// Generate audio
$audio_data = [
    'text' => $script,
    'voiceId' => '7ZkBWSrJynvq6BBQZOnf'
];
$audio_response = $elevenlabs_api->textToSpeechWithVoiceId($audio_data['voiceId'], $audio_data);
$audio_file = 'voices/' . time() . '.mp3';
file_put_contents($audio_file, $audio_response->getBody());

// Create MeltProject
$project = new MeltProject();

// Add images to project
foreach ($images['data'] as $image) {
    $project->addImage($image['url'], 5, 1);
}

// Add audio to project
$project->setVoiceover($audio_file);

// Save project
$project->save('scene.xml');

// Log data
$log->info('Data:', $log_data);